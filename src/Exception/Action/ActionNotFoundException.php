<?php

declare(strict_types=1);

namespace Lium\Framework\Exception\Action;

use InvalidArgumentException;
use Lium\Framework\Exception\ExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ActionNotFoundException extends InvalidArgumentException implements ExceptionInterface, NotFoundExceptionInterface
{
    private ?string $action;
    private ?string $route;

    /** @var string[] */
    private array $availableActions;

    public function __construct(?string $action, ?string $route, array $availableActions = [])
    {
        $message = sprintf(
            'Action "%s" not found for route "%s"',
            $action ?? 'NULL',
            $route ?? 'NULL'
        );

        parent::__construct($message);

        $this->action = $action;
        $this->route = $route;
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->availableActions = $availableActions;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function getAvailableActions(): array
    {
        return $this->availableActions;
    }
}
