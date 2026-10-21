<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Request;

use Sidus\AdminBundle\Model\Action;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Represents a redirection to an admin action
 */
final readonly class RedirectActionResponse implements ActionResponseInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private Action $action,
        private ?object $entity = null,
        private array $parameters = [],
        private int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        private int $status = 302,
    ) {
    }

    public function getAction(): Action
    {
        return $this->action;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getReferenceType(): int
    {
        return $this->referenceType;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function withAction(Action $action): self
    {
        return new self($action, $this->entity, $this->parameters, $this->referenceType, $this->status);
    }
}
