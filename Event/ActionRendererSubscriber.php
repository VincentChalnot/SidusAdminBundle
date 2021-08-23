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

use Sidus\AdminBundle\Request\ActionResponse;
use Sidus\AdminBundle\Templating\TemplateResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * Renders the action
 */
class ActionRendererSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected TemplateResolverInterface $templateResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ViewEvent::class => 'renderAction',
        ];
    }

    public function renderAction(ViewEvent $event): void
    {
        $response = $event->getControllerResult();
        if (!$response instanceof ActionResponse) {
            return;
        }

        $action = $response->getAction();
        $template = $this->templateResolver->getTemplate($action);

        $parameters = array_merge(
            [
                'action' => $action,
                'admin' => $action->getAdmin(),
            ],
            $action->getTemplateParameters(),
            $response->getParameters()
        );

        $event->setResponse(
            new Response($template->render($parameters))
        );
    }
}
