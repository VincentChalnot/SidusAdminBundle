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

    /** @var string */
    protected $currentAction;

    /** @var mixed */
    protected $defaultFormType;

    /** @var string */
    protected $baseTemplate;

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
        $this->defaultFormType = $adminConfiguration['default_form_type'];
        $this->baseTemplate = $adminConfiguration['base_template'];
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

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $option
     * @param mixed $default
     * @return array
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
     * @return bool
     */
    public function hasOption($option)
    {
        return array_key_exists($option, $this->options);
    }

    /**
     * @return mixed
     */
    public function getDefaultFormType()
    {
        return $this->defaultFormType;
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
