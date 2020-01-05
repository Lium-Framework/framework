<?php

declare(strict_types=1);

namespace Lium\Framework\Exception\Action;

use Lium\Framework\Exception\ExceptionInterface;
use Psr\Http\Server\RequestHandlerInterface;

class InvalidActionTypeException extends InvalidActionException implements ExceptionInterface
{
    public function __construct(object $action)
    {
        parent::__construct(
            $action,
            sprintf('The action must implements the %s interface.', RequestHandlerInterface::class)
        );
    }
}
