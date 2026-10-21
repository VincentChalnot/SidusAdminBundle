<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Model\PermissionCheck;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Request\RedirectActionResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Base class of the actions that hand an entity to the edit action (create, clone): once the entity is saved, the user
 * is redirected to the first of the edit, read and list actions of the admin he is allowed to run.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract readonly class AbstractEditDelegatingAction
{
    public function __construct(
        protected EditAction $editAction,
        protected AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    protected function edit(Request $request, Action $action, object $data): ActionResponseInterface
    {
        $response = ($this->editAction)($request, $action, $data);

        if ($response instanceof RedirectActionResponse) {
            return $this->updateRedirectAction($response);
        }

        return $response;
    }

    private function updateRedirectAction(RedirectActionResponse $response): RedirectActionResponse
    {
        $admin = $response->getAction()->getAdmin();

        foreach (['edit', 'read', 'list'] as $actionCode) {
            if ($admin->hasAction($actionCode)
                && $this->authorizationChecker->isGranted(null, new PermissionCheck($admin->getAction($actionCode), $response->getEntity()))
            ) {
                return $response->withAction($admin->getAction($actionCode));
            }
        }

        return $response;
    }
}
