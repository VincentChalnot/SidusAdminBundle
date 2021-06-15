<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Event;

use LogicException;
use Sidus\AdminBundle\Action\ActionInjectableInterface;
use Sidus\AdminBundle\Admin\Admin;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use UnexpectedValueException;
use function is_array;

/**
 * Injects the active admin in the controller
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminControllerInjecter
{
    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (is_array($controller)) {
            [$controller] = $controller; // Ignoring action
        }
        if (!$controller instanceof ActionInjectableInterface) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->attributes->has('_admin')) {
            $routeName = $request->attributes->get('_route');

            $m = "Missing request attribute '_admin' for route {$routeName}, this means you declared";
            $m .= ' this route outside the admin configuration, please use the admin configuration';
            throw new LogicException($m);
        }
        $admin = $request->attributes->get('_admin');
        if (!$admin instanceof Admin) {
            throw new UnexpectedValueException('_admin request attribute is not an Admin object');
        }

        if (!$request->attributes->has('_action')) {
            $routeName = $request->attributes->get('_route');

            $m = "Missing request attribute '_action' for route {$routeName},";
            $m .= 'this means you declared this route outside the admin configuration, please include the _action';
            $m .= 'attribute in your route definition or use the admin configuration';
            throw new LogicException($m);
        }
        $admin->setCurrentAction($request->attributes->get('_action'));
        $controller->setAction($admin->getCurrentAction());
    }
}
