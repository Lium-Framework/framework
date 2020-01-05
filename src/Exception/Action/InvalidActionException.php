<?php

declare(strict_types=1);

namespace Lium\Framework\Exception\Action;

use Lium\Framework\Exception\ExceptionInterface;

class InvalidActionException extends \InvalidArgumentException implements ExceptionInterface
{
    /** @var object */
    protected $action;

    /** @var string */
    protected $reasonPhrase;

    public function __construct(object $action, string $reasonPhrase = '')
    {
        $message = sprintf(
            'The action "%s" is invalid. %s',
            get_class($action),
            $reasonPhrase
        );

        parent::__construct($message);

        $this->action = $action;
        $this->reasonPhrase = $reasonPhrase;
    }

    public function getAction(): object
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
