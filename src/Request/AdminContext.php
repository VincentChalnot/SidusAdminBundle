<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Request;

use LogicException;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\Admin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tells which admin and action the request being handled is for. Stateless: it keeps nothing, every call reads the
 * `_admin` and `_action` attributes of the current request (the main request when the current one, e.g. a fragment, is
 * not an admin request itself), so it can never return the data of a previous request.
 *
 * The attributes are turned into objects by {@see \Sidus\AdminBundle\Event\AdminResolverSubscriber}, when the request is
 * received.
 */
final readonly class AdminContext
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getAdmin(): ?Admin
    {
        $admin = $this->attribute('_admin');

        return $admin instanceof Admin ? $admin : null;
    }

    public function getAction(): ?Action
    {
        $action = $this->attribute('_action');

        return $action instanceof Action ? $action : null;
    }

    public function requireAdmin(): Admin
    {
        return $this->getAdmin() ?? throw new LogicException('There is no current admin: the current request is not an admin request.');
    }

    public function requireAction(): Action
    {
        return $this->getAction() ?? throw new LogicException('There is no current action: the current request is not an admin request.');
    }

    private function attribute(string $name): mixed
    {
        foreach ([$this->requestStack->getCurrentRequest(), $this->requestStack->getMainRequest()] as $request) {
            if ($request instanceof Request && $request->attributes->has($name)) {
                return $request->attributes->get($name);
            }
        }

        return null;
    }
}
