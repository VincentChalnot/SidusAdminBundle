<?php

namespace Sidus\AdminBundle\Event;


use Sidus\AdminBundle\Configuration\AdminConfigurationHandler;
use Sidus\AdminBundle\Controller\AdminInjectable;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Twig_Environment;

class AdminControllerInjecter
{
    /** @var AdminConfigurationHandler */
    protected $adminConfigurationHandler;

    /** @var Twig_Environment */
    protected $twig;

    /**
     * @param AdminConfigurationHandler $adminConfigurationHandler
     * @param Twig_Environment $twig
     */
    public function __construct(AdminConfigurationHandler $adminConfigurationHandler, Twig_Environment $twig)
    {
        $this->adminConfigurationHandler = $adminConfigurationHandler;
        $this->twig = $twig;
    }

    /**
     * @param FilterControllerEvent $event
     * @throws \LogicException|\UnexpectedValueException|\InvalidArgumentException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        list($controller, $action) = $controller;
        if (!$controller instanceof AdminInjectable) {
            return;
        }
        if (!$event->getRequest()->attributes->has('_admin')) {
            $routeName = $event->getRequest()->attributes->get('_route');
            throw new \LogicException("Missing request attribute '_admin' for route {$routeName},".
                'this means you declared this route outside the admin configuration, please include the _admin'.
                'attribute in your route definition or use the admin configuration');
        }
        $admin = $this->adminConfigurationHandler->getAdmin($event->getRequest()->attributes->get('_admin'));
        $controller->setAdmin($admin);
        $this->twig->addGlobal('admin', $admin);
        $this->adminConfigurationHandler->setCurrentAdmin($admin);
    }
}
