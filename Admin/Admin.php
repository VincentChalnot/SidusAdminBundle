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

    /** @var string */
    protected $controller;

    /** @var string */
    protected $prefix;

    /** @var Action[] */
    protected $actions = [];

    /** @var array */
    protected $options = [];

    /** @var string */
    protected $entity;

    /** @var Action */
    protected $currentAction;

    /** @var string */
    protected $fallbackTemplateDirectory;

    /** @var string */
    protected $baseTemplate;

    /**
     * Admin constructor.
     *
     * @param string $code
     * @param array  $adminConfiguration
     */
    public function __construct($code, array $adminConfiguration)
    {
        $this->code = $code;
        $this->controller = $adminConfiguration['controller'];
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
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return Action[]
     */
    public function getActions()
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
    public function getAction($code)
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
    public function hasAction($code)
    {
        return !empty($this->actions[$code]);
    }

    /**
     * @param string $route
     *
     * @return bool
     */
    public function hasRoute($route)
    {
        foreach ($this->getActions() as $action) {
            if ($action->getRouteName() === $route) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($option, $default = null)
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
    public function hasOption($option)
    {
        return array_key_exists($option, $this->options);
    }

    /**
     * @return string
     */
    public function getFallbackTemplateDirectory()
    {
        return $this->fallbackTemplateDirectory;
    }

    /**
     * @return Action
     */
    public function getCurrentAction()
    {
        return $this->currentAction;
    }

    /**
     * @param string|Action $action
     *
     * @throws \UnexpectedValueException
     */
    public function setCurrentAction($action)
    {
        if (!$action instanceof Action) {
            $action = $this->getAction($action);
        }
        $this->currentAction = $action;
    }

    /**
     * @return string
     */
    public function getBaseTemplate()
    {
        return $this->baseTemplate;
    }
}
