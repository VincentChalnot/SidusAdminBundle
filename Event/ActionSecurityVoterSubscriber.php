<?php
declare(strict_types=1);

namespace Sidus\AdminBundle\Event;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\PermissionCheck;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ActionSecurityVoterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => ['onControllerEvent', -100],
            ControllerArgumentsEvent::class => ['onControllerArgumentsEvent', -100],
        ];
    }

    /**
     * Control access to an action
     */
    public function onControllerEvent(ControllerEvent $event): void
    {
        $action = $event->getRequest()->attributes->get('_action');
        if (!$action instanceof Action) {
            return;
        }

        $subject = new PermissionCheck($action);
        if (!$this->authorizationChecker->isGranted(null, $subject)) {
            throw new AccessDeniedException(
                "Access denied to action {$action->getAdmin()->getCode()}:{$action->getCode()}",
            );
        }
    }

    /**
     * Control access to a specific entity
     */
    public function onControllerArgumentsEvent(ControllerArgumentsEvent $event): void
    {
        $action = $event->getRequest()->attributes->get('_action');
        if (!$action instanceof Action) {
            return;
        }

        $entityClass = $action->getAdmin()->getEntity();
        foreach ($event->getArguments() as $argument) {
            if (!is_a($argument, $entityClass)) {
                continue;
            }

            $subject = new PermissionCheck($action, $argument);
            if (!$this->authorizationChecker->isGranted(null, $subject)) {
                throw new AccessDeniedException(
                    "Access denied to action {$action->getAdmin()->getCode()}:{$action->getCode()}",
                );
            }
        }
    }
}
