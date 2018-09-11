<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Routing;

use Sidus\AdminBundle\Configuration\AdminRegistry;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads all routes contained in actions
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminRouteLoader extends Loader
{
    /** @var bool */
    protected $loaded;

    /** @var AdminRegistry */
    protected $adminRegistry;

    /**
     * @param AdminRegistry $adminRegistry
     */
    public function __construct(AdminRegistry $adminRegistry)
    {
        $this->adminRegistry = $adminRegistry;
    }

    /**
     * @param mixed $resource
     * @param null  $type
     *
     * @throws \RuntimeException
     *
     * @return RouteCollection
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "sidus_admin" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->adminRegistry->getAdmins() as $admin) {
            foreach ($admin->getActions() as $action) {
                $routes->add($action->getRouteName(), $action->getRoute());
            }
        }

        $this->loaded = true;

        return $routes;
    }

    /**
     * @param mixed  $resource
     * @param string $type
     *
     * @return bool
     */
    public function supports($resource, $type = null)
    {
        return 'sidus_admin' === $type;
    }
}
