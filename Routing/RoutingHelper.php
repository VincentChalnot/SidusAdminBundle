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

namespace Sidus\AdminBundle\Routing;

use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Admin\Admin;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides a simple way to access routing utilities from a controller or an action
 */
class RoutingHelper
{
    public function __construct(protected AdminRouter $adminRouter)
    {
    }

    public function redirectToEntity(
        Action $action,
        object $entity,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        int $status = 302
    ): RedirectResponse {
        $url = $this->adminRouter->generateAdminEntityPath(
            $action->getAdmin(),
            $entity,
            $action->getCode(),
            $parameters,
            $referenceType
        );

        return new RedirectResponse($url, $status);
    }

    public function redirectToAction(
        Action $action,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        int $status = 302
    ): RedirectResponse {
        $url = $this->adminRouter->generateAdminPath(
            $action->getAdmin(),
            $action->getCode(),
            $parameters,
            $referenceType
        );

        return new RedirectResponse($url, $status);
    }

    public function getAdminListPath(Admin $admin, array $parameters = []): ?string
    {
        if (!$admin->hasAction('list')) {
            return null;
        }

        return $this->adminRouter->generateAdminPath($admin, 'list', $parameters);
    }

    public function getCurrentUri(Action $action, Request $request, array $parameters = []): string
    {
        if ($request->attributes->get('_route') === $action->getRouteName()) {
            $parameters = array_merge(
                $request->attributes->get('_route_params'),
                $parameters
            );
        }

        return $this->adminRouter->generateAdminPath($action->getAdmin(), $action->getCode(), $parameters);
    }
}
