<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Request;

use Sidus\AdminBundle\Model\Action;

/**
 * Asks to render the template of an action with these parameters
 */
final readonly class ActionResponse implements ActionResponseInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private Action $action,
        private array $parameters,
    ) {
    }

    public function getAction(): Action
    {
        return $this->action;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
