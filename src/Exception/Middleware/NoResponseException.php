<?php

declare(strict_types=1);

namespace Lium\Framework\Exception\Middleware;

use Lium\Framework\Exception\ExceptionInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class NoResponseException extends LogicException implements ExceptionInterface
{
    public function __construct(int $code = 0, Throwable $previous = null)
    {
        $message = sprintf(
            'The last middleware of the stack must return an object of type "%s"',
            ResponseInterface::class
        );

        parent::__construct($message, $code, $previous);
    }
}
