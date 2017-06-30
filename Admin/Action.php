<?php

namespace Sidus\AdminBundle\Admin;

use Symfony\Component\Routing\Route;

/**
 * Holds information about an action and it's related route and template
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Action
{
    /** @var string */
    protected $code;

    /** @var Route */
    protected $route;

    /** @var Admin */
    protected $admin;

    /** @var mixed */
    protected $formType;

    /** @var array */
    protected $formOptions;

    /** @var string */
    protected $template;

    /**
     * @param string $code
     * @param Admin  $admin
     * @param array  $c
     */
    public function __construct($code, Admin $admin, array $c)
    {
        $this->code = $code;
        $this->admin = $admin;
        $this->formType = $c['form_type'];
        $this->formOptions = $c['form_options'];
        $this->template = $c['template'];

        $defaults = array_merge(
            [
                '_controller' => $admin->getController().':'.$code,
                '_admin' => $admin->getCode(),
            ],
            $c['defaults']
        );

        $this->route = new Route(
            $this->getAdmin()->getPrefix().$c['path'],
            $defaults,
            $c['requirements'],
            $c['options'],
            $c['host'],
            $c['schemes'],
            $c['methods'],
            $c['condition']
        );
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return "sidus_admin.{$this->getAdmin()->getCode()}.{$this->getCode()}";
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return Admin
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @return array
     */
    public function getFormOptions()
    {
        return $this->formOptions;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
