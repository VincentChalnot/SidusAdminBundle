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
use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use UnexpectedValueException;

/**
 * Resolve the _admin and _action request attributes to real objects
 */
class AdminResolverSubscriber implements EventSubscriberInterface
{
    public function __construct(protected AdminRegistry $adminRegistry)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->attributes->has('_admin')) {
            return;
        }
        $admin = $request->attributes->get('_admin');
        if (!$admin instanceof Admin) {
            $admin = $this->adminRegistry->getAdmin($admin);
            $request->attributes->set('_admin', $admin);
        }
        $this->adminRegistry->setCurrentAdmin($admin);

        if (!$request->attributes->has('_action')) {
            throw new UnexpectedValueException('Missing _action request attribute');
        }
        $action = $request->attributes->get('_action');
        if (!$action instanceof Action) {
            $action = $admin->getAction($action);
            $request->attributes->set('_action', $action);
        }

        if ($action->getAdmin()->getCode() !== $admin->getCode()) {
            throw new LogicException('Current action does not belong to current admin');
        }
    }
}
