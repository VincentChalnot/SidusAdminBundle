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

namespace Sidus\AdminBundle\Admin;

use UnexpectedValueException;

/**
 * The admin serves as an action holder and is attached to a Doctrine entity
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Admin
{
    protected string $code;

    protected array $controllerPattern = [];

    protected string $baseTemplate;

    protected array $templatePattern = [];

    protected ?string $prefix;

    protected array $actions = [];

    protected array $options = [];

    protected ?string $entity;

    protected ?string $formType;

    protected ?Action $currentAction;

    public function __construct(string $code, array $adminConfiguration)
    {
        $this->code = $code;
        $this->controllerPattern = $adminConfiguration['controller_pattern'];
        $this->baseTemplate = $adminConfiguration['base_template'];
        $this->templatePattern = $adminConfiguration['template_pattern'];
        $this->prefix = $adminConfiguration['prefix'];
        $this->entity = $adminConfiguration['entity'];
        $this->formType = $adminConfiguration['form_type'];
        $this->options = $adminConfiguration['options'];

        $actionClass = $adminConfiguration['action_class'];
        foreach ((array) $adminConfiguration['actions'] as $actionCode => $actionConfiguration) {
            if (!isset($actionConfiguration['base_template'])) {
                $actionConfiguration['base_template'] = $adminConfiguration['base_template'];
            }
            $this->actions[$actionCode] = new $actionClass($actionCode, $this, $actionConfiguration);
        }
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getControllerPattern(): array
    {
        return $this->controllerPattern;
    }

    public function getTemplatePattern(): array
    {
        return $this->templatePattern;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @return Action[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function getAction(string $code): Action
    {
        if (!$this->hasAction($code)) {
            throw new UnexpectedValueException("No action with code: '{$code}' for admin '{$this->getCode()}'");
        }

        return $this->actions[$code];
    }

    public function hasAction(string $code): bool
    {
        return !empty($this->actions[$code]);
    }

    public function hasRoute(string $route): bool
    {
        foreach ($this->getActions() as $action) {
            if ($action->getRouteName() === $route) {
                return true;
            }
        }

        return false;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $option, mixed $default = null)
    {
        if (!$this->hasOption($option)) {
            return $default;
        }

        return $this->options[$option];
    }

    public function hasOption(string $option): bool
    {
        return array_key_exists($option, $this->options);
    }

    public function getCurrentAction(): ?Action
    {
        return $this->currentAction;
    }

    public function setCurrentAction($action): void
    {
        if (!$action instanceof Action) {
            $action = $this->getAction($action);
        }
        $this->currentAction = $action;
    }

    public function getBaseTemplate(): ?string
    {
        return $this->baseTemplate;
    }
}
