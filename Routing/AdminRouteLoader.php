<?php

namespace Sidus\AdminBundle\Routing;

use Sidus\AdminBundle\Configuration\AdminConfigurationHandler;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

class AdminRouteLoader extends Loader
{
    /** @var bool */
    protected $loaded;

    /** @var AdminConfigurationHandler */
    protected $adminConfigurationHandler;

    /**
     * AdminRouteLoader constructor.
     * @param AdminConfigurationHandler $adminConfigurationHandler
     */
    public function __construct(AdminConfigurationHandler $adminConfigurationHandler)
    {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
    }

    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "sidus_admin" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->adminConfigurationHandler->getAdmins() as $admin) {
            foreach ($admin->getActions() as $action) {
                $routes->add($action->getRouteName(), $action->getRoute());
            }
        }

        $this->loaded = true;
        return $routes;
    }

    public function supports($resource, $type = null)
    {
        return 'sidus_admin' === $type;
    }
}
