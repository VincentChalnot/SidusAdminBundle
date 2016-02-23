<?php

namespace Sidus\AdminBundle\Admin;

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

    /**
     * Admin constructor.
     * @param string $code
     * @param array $adminConfiguration
     */
    public function __construct($code, array $adminConfiguration)
    {
        $this->code = $code;
        $this->controller = $adminConfiguration['controller'];
        $this->prefix = $adminConfiguration['prefix'];
        $actionClass = $adminConfiguration['action_class'];
        $this->entity = $adminConfiguration['entity'];
        $this->options = $adminConfiguration['options'];
        foreach ($adminConfiguration['actions'] as $actionCode => $actionConfiguration) {
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
     * @param $code
     * @return Action
     * @throws \UnexpectedValueException
     */
    public function getAction($code)
    {
        if (empty($this->actions[$code])) {
            throw new \UnexpectedValueException("No action with code: '{$code}' for admin '{$this->getCode()}'");
        }
        return $this->actions[$code];
    }

    /**
     * @param $route
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

    public function getEntity()
    {
        return $this->entity;
    }
}