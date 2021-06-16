<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Request;

use Sidus\AdminBundle\Admin\Action;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Represents a redirection to an admin action
 */
class RedirectActionResponse implements ActionResponseInterface
{
    public function __construct(
        protected Action $action,
        protected ?object $entity = null,
        protected array $parameters = [],
        protected int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        protected int $status = 302
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
        $new = clone $this;
        $new->action = $action;

        return $new;
    }
}
