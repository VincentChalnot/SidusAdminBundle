<?php

namespace Sidus\AdminBundle\Event;

use Sidus\AdminBundle\Configuration\AdminConfigurationHandler;
use Sidus\AdminBundle\Controller\AdminInjectableInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Twig_Environment;

/**
 * Injects the active admin in the controller
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminControllerInjecter
{
    /** @var AdminConfigurationHandler */
    protected $adminConfigurationHandler;

    /**
     * @param AdminConfigurationHandler $adminConfigurationHandler
     */
    public function __construct(AdminConfigurationHandler $adminConfigurationHandler)
    {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
    }

    /**
     * @param FilterControllerEvent $event
     *
     * @throws \LogicException|\UnexpectedValueException|\InvalidArgumentException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        list($controller, $action) = $controller;
        if (!$controller instanceof AdminInjectableInterface) {
            return;
        }
        if (!$event->getRequest()->attributes->has('_admin')) {
            $routeName = $event->getRequest()->attributes->get('_route');

            $m = "Missing request attribute '_admin' for route {$routeName},";
            $m .= 'this means you declared this route outside the admin configuration, please include the _admin';
            $m .= 'attribute in your route definition or use the admin configuration';
            throw new \LogicException($m);
        }
        $admin = $this->adminConfigurationHandler->getAdmin($event->getRequest()->attributes->get('_admin'));
        $admin->setCurrentAction(substr($action, 0, -strlen('Action')));
        $controller->setAdmin($admin);
        $this->adminConfigurationHandler->setCurrentAdmin($admin);
    }
}
