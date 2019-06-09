<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Event;

use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Admin\Admin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Resolve the proper controller when the _controller_pattern option is used
 */
class AdminControllerResolver
{
    /** @var ContainerControllerResolver */
    public $controllerResolver;

    /**
     * @param ContainerControllerResolver $controllerResolver
     */
    public function __construct(ContainerControllerResolver $controllerResolver)
    {
        $this->controllerResolver = $controllerResolver;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->attributes->has('_controller')) {
            return;
        }
        if (!$request->attributes->has('_controller_pattern')) {
            return;
        }

        $controllerPattern = $request->attributes->get('_controller_pattern');
        if (!\is_array($controllerPattern)) {
            throw new \UnexpectedValueException("'_controller_pattern' must be an array");
        }
        $admin = $request->attributes->get('_admin');
        $action = $request->attributes->get('_action');
        if (!$admin instanceof Admin) {
            throw new \UnexpectedValueException('_admin request attribute is not an Admin object');
        }
        if (!$action instanceof Action) {
            throw new \UnexpectedValueException('_action request attribute is not an Action object');
        }
        $controller = $this->getController($request, $admin, $action, $controllerPattern);
        $request->attributes->set('_controller', $controller);
    }

    /**
     * @param Request $request
     * @param Admin   $admin
     * @param Action  $action
     * @param array   $controllerPatterns
     *
     * @return callable|false
     */
    protected function getController(Request $request, Admin $admin, Action $action, array $controllerPatterns)
    {
        foreach ($controllerPatterns as $controllerPattern) {
            $controller = strtr(
                $controllerPattern,
                [
                    '{{admin}}' => lcfirst($admin->getCode()),
                    '{{Admin}}' => ucfirst($admin->getCode()),
                    '{{action}}' => lcfirst($action->getCode()),
                    '{{Action}}' => ucfirst($action->getCode()),
                ]
            );
            $testRequest = clone $request;
            $testRequest->attributes->set('_controller', $controller);
            try {
                $resolvedController = $this->controllerResolver->getController($testRequest);
            } catch (\LogicException $e) {
                continue;
            }

            if (false !== $resolvedController) {
                return $resolvedController;
            }
        }

        $flattened = implode(', ', $controllerPatterns);
        $m = "Unable to resolve any valid controller for the admin '{$admin->getCode()}' and action ";
        $m .= "'{$action->getCode()}' and for the controller_pattern configuration: {$flattened}";
        throw new \RuntimeException($m);
    }
}
