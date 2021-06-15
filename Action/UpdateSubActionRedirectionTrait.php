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

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Admin\Action;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Common logic found in clone and create, does not deserves a dedicated service
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
trait UpdateSubActionRedirectionTrait
{
    protected AuthorizationCheckerInterface $authorizationChecker;

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
