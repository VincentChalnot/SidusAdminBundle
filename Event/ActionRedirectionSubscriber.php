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

use Sidus\AdminBundle\Request\RedirectActionResponse;
use Sidus\AdminBundle\Routing\AdminRouter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Redirects to the proper action
 */
class ActionRedirectionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected AdminRouter $adminRouter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ViewEvent::class => 'redirectAction',
        ];
    }

    public function redirectAction(ViewEvent $event): void
    {
        $response = $event->getControllerResult();
        if (!$response instanceof RedirectActionResponse) {
            return;
        }

        $action = $response->getAction();
        if ($response->getEntity()) {
            $url = $this->adminRouter->generateAdminEntityPath(
                $action->getAdmin(),
                $response->getEntity(),
                $action->getCode(),
                $response->getParameters(),
                $response->getReferenceType()
            );
        } else {
            $url = $this->adminRouter->generateAdminPath(
                $action->getAdmin(),
                $action->getCode(),
                $response->getParameters(),
                $response->getReferenceType()
            );
        }

        $event->setResponse(new RedirectResponse($url, $response->getStatus()));
    }
}
