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

use RuntimeException;
use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Admin\Admin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use UnexpectedValueException;
use function is_array;

/**
 * Resolve the proper controller when the _controller_pattern option is used and sets the _controller request attribute
 */
class AdminControllerResolverSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', -1],
        ];
    }

    public function __construct(protected ControllerResolverInterface $controllerResolver)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->attributes->has('_controller')) {
            return;
        }
        if (!$request->attributes->has('_controller_pattern')) {
            return;
        }

        $controllerPattern = $request->attributes->get('_controller_pattern');
        if (!is_array($controllerPattern)) {
            throw new UnexpectedValueException("'_controller_pattern' must be an array");
        }
        $admin = $request->attributes->get('_admin');
        $action = $request->attributes->get('_action');
        if (!$admin instanceof Admin) {
            throw new UnexpectedValueException('_admin request attribute is not an Admin object');
        }
        if (!$action instanceof Action) {
            throw new UnexpectedValueException('_action request attribute is not an Action object');
        }
        $controller = $this->getController($request, $admin, $action, $controllerPattern);
        $request->attributes->set('_controller', $controller);
    }

    protected function getController(
        Request $request,
        Admin $admin,
        Action $action,
        array $controllerPatterns
    ): callable {
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
            } catch (\InvalidArgumentException $exception) {
                // Throw the exception if it's not about a missing service for easier debugging
                if (!str_contains($exception->getMessage(), 'does neither exist as service nor as class')) {
                    throw $exception;
                }
                continue;
            }

            if (false !== $resolvedController) {
                return $resolvedController;
            }
        }

        $flattened = implode(', ', $controllerPatterns);
        $m = "Unable to resolve any valid controller for the admin '{$admin->getCode()}' and action ";
        $m .= "'{$action->getCode()}' and for the controller_pattern configuration: {$flattened}";
        throw new RuntimeException($m);
    }
}
