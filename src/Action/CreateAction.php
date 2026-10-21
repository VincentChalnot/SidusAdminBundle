<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Request\RedirectActionResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsController]
class CreateAction implements ActionInjectableInterface
{
    use ActionInjectableTrait;
    use RedirectionTrait;

    public function __construct(
        protected EditAction $editAction,
        AuthorizationCheckerInterface $authorizationChecker,
    ) {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function __invoke(Request $request): ActionResponseInterface
    {
        $class = $this->action->getAdmin()->getEntity();

        $this->editAction->setAction($this->action);
        $response = ($this->editAction)($request, new $class());

        if ($response instanceof RedirectActionResponse) {
            return $this->updateRedirectAction($response);
        }

        return $response;
    }
}
