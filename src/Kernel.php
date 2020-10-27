<?php

declare(strict_types=1);

namespace Lium\Framework;

use Exception;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Lium\Framework\DependencyInjection\FrameworkExtension;
use LogicException;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * The kernel of the application
 */
abstract class Kernel
{
    protected string $env;
    protected bool $debug;
    protected ?ContainerInterface $container;
    private bool $booted;

    /** @var array<string, ExtensionInterface> */
    private array $extensions;

    public function __construct(string $env, bool $debug = false)
    {
        $this->env = $env;
        $this->debug = $debug;
        $this->container = null;
        $this->booted = false;
        $this->extensions = [];
    }

    /**
     * Run the application's kernel
     *
     * @throws Exception
     */
    public function run(): void
    {
        $this->boot();

        if ($this->container === null) {
            throw new LogicException('The container should be initialized in the boot() method');
        }

        /** @var ServerRequestCreatorInterface $serverRequestCreator */
        $serverRequestCreator = $this->container->get(ServerRequestCreatorInterface::class);
        $request = $serverRequestCreator->fromGlobals();

        /** @var MiddlewareRunnerInterface $middlewareRunner */
        $middlewareRunner = $this->container->get(MiddlewareRunnerInterface::class);
        $response = $middlewareRunner->handle($request);

        /** @var EmitterInterface $emitter */
        $emitter = $this->container->get(EmitterInterface::class);
        $emitter->emit($response);
    }

    abstract public function getProjectDir(): string;

    public function getConfigDir(): string
    {
        return $this->getProjectDir().'/config';
    }

    public function getCacheDir(): string
    {
        return sprintf('%s/var/cache/%s', $this->getProjectDir(), $this->env);
    }

    public function addExtensions(ExtensionInterface ...$extensions): void
    {
        foreach ($extensions as $extension) {
            $this->extensions[$extension->getAlias()] = $extension;
        }
    }

    /**
     * Prepare the kernel to run the application
     *
     * @throws Exception
     *
     * @psalm-suppress UnresolvableInclude
     */
    protected function boot(): void
    {
        if ($this->booted === true) {
            return;
        }

        $containerDumpFile = $this->getBuiltContainerFilename();

        if ($this->debug === true || file_exists($containerDumpFile) === false) {
            $this->buildContainer($containerDumpFile);
        }

        require_once $containerDumpFile;

        /** @var ContainerInterface */
        $this->container = new \BuiltContainer;

        $this->booted = true;
    }

    abstract protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void;

    protected function getContainerLoader(ContainerBuilder $container): LoaderInterface
    {
        $locator = new FileLocator($this->getConfigDir());
        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new DelegatingLoader($resolver);
    }

    protected function getBuiltContainerFilename(): string
    {
        return sprintf('%s/container.php', $this->getCacheDir());
    }

    /**
     * Build the container based on the application's configuration and dump it to the given file
     *
     * @param string $containerDumpFile
     *
     * @throws Exception
     */
    private function buildContainer(string $containerDumpFile): void
    {
        $containerBuilder = new ContainerBuilder();

        $this->configureContainer($containerBuilder, $this->getContainerLoader($containerBuilder));

        $this->addExtensions(new FrameworkExtension());
        $this->registerExtensions($containerBuilder);

        $containerBuilder->setParameter('app.environment', $this->env);
        $containerBuilder->setParameter('app.debug', $this->debug);
        $containerBuilder->setParameter('app.project_dir', $this->getProjectDir());
        $containerBuilder->setParameter('app.config_dir', $this->getConfigDir());
        $containerBuilder->setParameter('app.cache_dir', $this->getCacheDir());

        $containerBuilder->compile();

        // dump the container
        @mkdir(dirname($containerDumpFile), 0777, true);
        file_put_contents(
            $containerDumpFile,
            (new PhpDumper($containerBuilder))->dump(['class' => 'BuiltContainer'])
        );
    }

    private function registerExtensions(ContainerBuilder $container): void
    {
        foreach ($this->extensions as $alias => $extension) {
            $container->registerExtension($extension);
            $container->loadFromExtension($alias);
        }
    }
}
