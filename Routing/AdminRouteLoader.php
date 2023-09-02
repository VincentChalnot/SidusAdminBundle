<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
    protected bool $isLoaded = false;

    public function __construct(
        protected AdminRegistry $adminRegistry,
        string $env = null,
    ) {
        parent::__construct($env);
    }

    public function load(mixed $resource, string $type = null): RouteCollection
    {
        if (true === $this->isLoaded) {
            throw new \RuntimeException('Do not add the "sidus_admin" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->adminRegistry->getAdmins() as $admin) {
            foreach ($admin->getActions() as $action) {
                $routes->add($action->getRouteName(), $action->getRoute());
            }
        }
        $this->isLoaded = true;

        return $routes;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return 'sidus_admin' === $type;
    }
}
