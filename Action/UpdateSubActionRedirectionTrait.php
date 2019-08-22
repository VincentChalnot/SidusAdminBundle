<?php declare(strict_types=1);
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2019 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Admin\Action;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Common logic found in clone and create, does not deserves a dedicated service
 */
trait UpdateSubActionRedirectionTrait
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * @param RedirectableInterface $redirectable
     * @param Action                $action
     */
    protected function updateRedirectAction(RedirectableInterface $redirectable, Action $action): void
    {
        $redirectable->setAction($action);
        $admin = $action->getAdmin();
        $class = $admin->getEntity();

        foreach (['edit', 'read'] as $actionCode) {
            if ($admin->hasAction($actionCode) && $this->authorizationChecker->isGranted($actionCode, $class)) {
                $redirectable->setRedirectAction($admin->getAction($actionCode));
                break;
            }
        }
    }
}
