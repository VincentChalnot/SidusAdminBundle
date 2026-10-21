<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Event;

use LogicException;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Sidus\AdminBundle\Registry\AdminRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use UnexpectedValueException;

/**
 * Resolves the `_admin` and `_action` request attributes (codes from the route defaults) to the real objects. This is
 * the only source of truth of "the current admin and action": nothing else is stored, anywhere.
 */
final readonly class AdminResolverSubscriber implements EventSubscriberInterface
{
    public function __construct(private AdminRegistry $adminRegistry)
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
            $admin = $this->adminRegistry->get((string) $admin);
            $request->attributes->set('_admin', $admin);
        }

        if (!$request->attributes->has('_action')) {
            throw new UnexpectedValueException('Missing _action request attribute, it is required when _admin is set');
        }
        $action = $request->attributes->get('_action');
        if (!$action instanceof Action) {
            $action = $admin->getAction((string) $action);
            $request->attributes->set('_action', $action);
        }

        if ($action->getAdmin()->getCode() !== $admin->getCode()) {
            throw new LogicException('Current action does not belong to current admin');
        }
    }
}
