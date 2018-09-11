<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Event;

use Sidus\AdminBundle\Action\ActionInjectableInterface;
use Sidus\AdminBundle\Configuration\AdminRegistry;
use Sidus\AdminBundle\Controller\AdminInjectableInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Injects the active admin in the controller
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminControllerInjecter
{
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
     * @param FilterControllerEvent $event
     *
     * @throws \LogicException|\UnexpectedValueException|\InvalidArgumentException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if (\is_array($controller)) {
            [$controller] = $controller; // Ignoring action
        }
        if (!$controller instanceof AdminInjectableInterface && !$controller instanceof ActionInjectableInterface) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->attributes->has('_admin')) {
            $routeName = $request->attributes->get('_route');

            $m = "Missing request attribute '_admin' for route {$routeName},";
            $m .= 'this means you declared this route outside the admin configuration, please include the _admin';
            $m .= 'attribute in your route definition or use the admin configuration';
            throw new \LogicException($m);
        }
        $admin = $this->adminRegistry->getAdmin($request->attributes->get('_admin'));
        $this->adminRegistry->setCurrentAdmin($admin);
        if ($controller instanceof AdminInjectableInterface) {
            $controller->setAdmin($admin);
        }

        if (!$request->attributes->has('_action')) {
            $routeName = $request->attributes->get('_route');

            $m = "Missing request attribute '_action' for route {$routeName},";
            $m .= 'this means you declared this route outside the admin configuration, please include the _action';
            $m .= 'attribute in your route definition or use the admin configuration';
            throw new \LogicException($m);
        }
        $admin->setCurrentAction($request->attributes->get('_action'));
        if ($controller instanceof ActionInjectableInterface) {
            $controller->setAction($admin->getCurrentAction());
        }
    }
}
