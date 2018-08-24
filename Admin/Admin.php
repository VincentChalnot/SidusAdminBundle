<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Admin;

/**
 * The admin serves as an action holder and is attached to a Doctrine entity
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Admin
{
    /** @var string */
    protected $code;

    /** @var string|null */
    protected $controller;

    /** @var array */
    protected $controllerPattern = [];

    /** @var string|null */
    protected $prefix;

    /** @var Action[] */
    protected $actions = [];

    /** @var array */
    protected $options = [];

    /** @var string|null */
    protected $entity;

    /** @var Action|null */
    protected $currentAction;

    /** @var string|null */
    protected $fallbackTemplateDirectory;

    /** @var string|null */
    protected $baseTemplate;

    /**
     * Admin constructor.
     *
     * @param string $code
     * @param array  $adminConfiguration
     */
    public function __construct(string $code, array $adminConfiguration)
    {
        $this->code = $code;
        $this->controller = $adminConfiguration['controller'];
        $this->controllerPattern = $adminConfiguration['controller_pattern'];
        $this->prefix = $adminConfiguration['prefix'];
        $actionClass = $adminConfiguration['action_class'];
        $this->entity = $adminConfiguration['entity'];
        $this->options = $adminConfiguration['options'];
        $this->fallbackTemplateDirectory = $adminConfiguration['fallback_template_directory'];
        $this->baseTemplate = $adminConfiguration['base_template'];

        foreach ((array) $adminConfiguration['actions'] as $actionCode => $actionConfiguration) {
            $this->actions[$actionCode] = new $actionClass($actionCode, $this, $actionConfiguration);
        }
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /**
     * @return array
     */
    public function getControllerPattern(): array
    {
        return $this->controllerPattern;
    }

    /**
     * @return string|null
     */
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

    /**
     * @param string $code
     *
     * @throws \UnexpectedValueException
     *
     * @return Action
     */
    public function getAction(string $code): Action
    {
        if (!$this->hasAction($code)) {
            throw new \UnexpectedValueException("No action with code: '{$code}' for admin '{$this->getCode()}'");
        }

        return $this->actions[$code];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasAction(string $code): bool
    {
        return !empty($this->actions[$code]);
    }

    /**
     * @param string $route
     *
     * @return bool
     */
    public function hasRoute(string $route): bool
    {
        foreach ($this->getActions() as $action) {
            if ($action->getRouteName() === $route) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string|null
     */
    public function getEntity(): ?string
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption(string $option, $default = null)
    {
        if (!$this->hasOption($option)) {
            return $default;
        }

        return $this->options[$option];
    }

    /**
     * @param string $option
     *
     * @return bool
     */
    public function hasOption(string $option): bool
    {
        return array_key_exists($option, $this->options);
    }

    /**
     * @return string|null
     */
    public function getFallbackTemplateDirectory(): ?string
    {
        return $this->fallbackTemplateDirectory;
    }

    /**
     * @return Action|null
     */
    public function getCurrentAction(): ?Action
    {
        return $this->currentAction;
    }

    /**
     * @param string|Action $action
     *
     * @throws \UnexpectedValueException
     */
    public function setCurrentAction($action): void
    {
        if (!$action instanceof Action) {
            $action = $this->getAction($action);
        }
        $this->currentAction = $action;
    }

    /**
     * @return string|null
     */
    public function getBaseTemplate(): ?string
    {
        return $this->baseTemplate;
    }
}
