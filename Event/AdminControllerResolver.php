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
    public function onKernelRequest(GetResponseEvent $event)
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
        if (null === $admin || null === $action) {
            throw new \UnexpectedValueException("Missing '_admin' or '_action' request attribute");
        }
        $controller = $this->getController($request, $admin, $action, $controllerPattern);
        $request->attributes->set('_controller', $controller);
    }

    /**
     * @param Request $request
     * @param string  $admin
     * @param string  $action
     * @param array   $controllerPatterns
     *
     * @return callable|false
     */
    protected function getController(Request $request, string $admin, string $action, array $controllerPatterns)
    {
        foreach ($controllerPatterns as $controllerPattern) {
            $controller = strtr(
                $controllerPattern,
                [
                    '{{admin}}' => lcfirst($admin),
                    '{{Admin}}' => ucfirst($admin),
                    '{{action}}' => lcfirst($action),
                    '{{Action}}' => ucfirst($action),
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
        throw new \RuntimeException(
            "Unable to resolve any valid controller for the controller_pattern configuration: {$flattened}"
        );
    }
}
