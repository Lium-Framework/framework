<?php

declare(strict_types=1);

namespace Lium\Framework\Middleware;

use Lium\Framework\Exception\Action\ActionNotFoundException;
use Lium\Framework\Exception\Action\InvalidActionTypeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * The action handler try to execute an action based on the request and return the response from it.
 * That's why this middleware has to be the last of the stack.
 */
final class ActionHandler implements MiddlewareInterface
{
    /** @var ServiceProviderInterface */
    private $actionLocator;

    public function __construct(ServiceProviderInterface $actionLocator)
    {
        $this->actionLocator = $actionLocator;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var string|null $actionClassName */
        $actionClassName = $request->getAttribute('action');
        /** @var string|null $route */
        $route = $request->getAttribute('route');

        if (null === $actionClassName) {
            throw new ActionNotFoundException(
                null,
                $route,
                array_keys($this->actionLocator->getProvidedServices())
            );
        }

        try {
            /** @var object $action */
            $action = $this->actionLocator->get($actionClassName);
        } catch (ServiceNotFoundException $serviceNotFoundException) {
            /** @var string|null $id */
            $id = $serviceNotFoundException->getId();

            throw new ActionNotFoundException(
                $id,
                $route,
                array_keys($this->actionLocator->getProvidedServices())
            );
        }

        if (!$action instanceof RequestHandlerInterface) {
            throw new InvalidActionTypeException($action);
        }

        return $action->handle($request);
    }
}
