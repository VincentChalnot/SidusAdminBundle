<?php

namespace Sidus\AdminBundle\Admin;

use Symfony\Component\Routing\Route;

class Action
{
    /** @var string */
    protected $code;

    /** @var Route */
    protected $route;

    /** @var Admin */
    protected $admin;

    /**
     * @param string $code
     * @param Admin $admin
     * @param $configuration
     */
    public function __construct($code, Admin $admin, array $configuration)
    {
        $this->code = $code;
        $this->admin = $admin;

        $c = $configuration;
        $defaults = array_merge([
            '_controller' => $admin->getController() . ':' . $code,
            '_admin' => $admin->getCode(),
        ], $c['defaults']);
        $this->route = new Route(
            $this->getAdmin()->getPrefix() . $c['path'],
            $defaults,
            $c['requirements'],
            $c['options'],
            $c['host'],
            $c['schemes'],
            $c['methods'],
            $c['condition']
        );
    }

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
}