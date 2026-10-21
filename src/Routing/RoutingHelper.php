<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Routing;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a simple way to access routing utilities from a controller or an action
 */
final readonly class RoutingHelper
{
    public function __construct(private AdminRouter $adminRouter)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function getAdminListPath(Admin $admin, array $parameters = []): ?string
    {
        if (!$admin->hasAction('list')) {
            return null;
        }

        return $this->adminRouter->generateAdminPath($admin, 'list', $parameters);
    }

    /**
     * The URI of the action, keeping the route parameters of the request when it is for this very action
     *
     * @param array<string, mixed> $parameters
     */
    public function getCurrentUri(Action $action, Request $request, array $parameters = []): string
    {
        if ($request->attributes->get('_route') === $action->getRouteName()) {
            $parameters = array_merge($request->attributes->get('_route_params', []), $parameters);
        }

        return $this->adminRouter->generateAdminPath($action->getAdmin(), $action->getCode(), $parameters);
    }
}
