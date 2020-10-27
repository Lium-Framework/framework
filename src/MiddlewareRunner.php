<?php

declare(strict_types=1);

namespace Lium\Framework;

use Lium\Framework\Exception\Middleware\NoResponseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Handles a server request and produces a response.
 *
 * An HTTP request handler process an HTTP request in order to produce an HTTP response.
 */
final class MiddlewareRunner implements MiddlewareRunnerInterface
{
    /** @var MiddlewareInterface[] */
    private array $queue;

    public function __construct(MiddlewareInterface ...$queue)
    {
        $this->queue = $queue;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = array_shift($this->queue);

        if ($middleware === null) {
            throw new NoResponseException();
        }

        return $middleware->process($request, $this);
    }
}
