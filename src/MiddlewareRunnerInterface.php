<?php

declare(strict_types=1);

namespace Lium\Framework;

use Lium\Framework\Exception\Middleware\NoResponseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewareRunnerInterface extends RequestHandlerInterface
{
    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws NoResponseException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
