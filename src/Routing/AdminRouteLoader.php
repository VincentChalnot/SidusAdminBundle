<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Routing;

use Sidus\AdminBundle\Registry\AdminRegistry;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads all routes contained in actions. Stateless: every call builds a new collection of new Route objects from the
 * immutable admins, so it can be called as many times as the router needs (cache refresh in a long-running worker).
 *
 *     # config/routes/admin.yaml
 *     sidus_admin:
 *         resource: .
 *         type: sidus_admin
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
final class AdminRouteLoader extends Loader
{
    public function __construct(
        private readonly AdminRegistry $adminRegistry,
        ?string $env = null,
    ) {
        parent::__construct($env);
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        foreach ($this->adminRegistry->all() as $admin) {
            foreach ($admin->getActions() as $action) {
                $routes->add($action->getRouteName(), $action->createRoute());
            }
        }

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'sidus_admin' === $type;
    }
}
