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

    /** @var array */
    protected $options;

    /** @var string|null */
    protected $formType;

    /** @var array */
    protected $formOptions;

    /** @var string|null */
    protected $template;

    /**
     * @param string $code
     * @param Admin  $admin
     * @param array  $c
     */
    public function __construct(string $code, Admin $admin, array $c)
    {
        $this->code = $code;
        $this->admin = $admin;
        $this->options = $c['options']; // Warning, options are used both here and for the route definition
        $this->formType = $c['form_type'];
        $this->formOptions = $c['form_options'];
        $this->template = $c['template'];

        $defaults = array_merge(
            [
                '_controller' => $admin->getController().':'.$code,
                '_admin' => $admin->getCode(),
                '_action' => $code,
            ],
            $c['defaults']
        );

        $this->route = new Route(
            $this->getAdmin()->getPrefix().$c['path'],
            $defaults,
            $c['requirements'],
            $c['options'], // Consider removing this as it might conflict with our options
            $c['host'],
            $c['schemes'],
            $c['methods'],
            $c['condition']
        );
    }

    /**
     * @return string
     */
    public function getRouteName(): string
    {
        return "sidus_admin.{$this->getAdmin()->getCode()}.{$this->getCode()}";
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return Route
     */
    public function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * @return Admin
     */
    public function getAdmin(): Admin
    {
        return $this->admin;
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
    public function getFormType(): ?string
    {
        return $this->formType;
    }

    /**
     * @return array
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }
}
