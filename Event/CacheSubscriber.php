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

use Sidus\AdminBundle\Admin\Admin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

/**
 * Add no-cache header to all Http Responses from the admin.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class CacheSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ResponseEvent::class => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->getRequest()->attributes->has('_admin')) {
            return;
        }

        $admin = $event->getRequest()->attributes->get('_admin');
        if (!$admin instanceof Admin) {
            throw new UnexpectedValueException('_admin request attribute is not an Admin object');
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                'Pragma' => 'private',
                'Expires' => 0,
            ]
        );

        $headers = $resolver->resolve($admin->getOption('http_cache', []));
        $event->getResponse()->headers->add($headers);
    }
}
