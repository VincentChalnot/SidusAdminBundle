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

use Sidus\AdminBundle\Attribute\AdminEntity;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Request\RedirectActionResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsController]
class CloneAction implements ActionInjectableInterface
{
    use ActionInjectableTrait;
    use RedirectionTrait;

    public function __construct(
        protected EditAction $editAction,
        AuthorizationCheckerInterface $authorizationChecker,
    ) {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function __invoke(
        Request $request,
        #[AdminEntity]
        object $data,
    ): ActionResponseInterface {
        $this->editAction->setAction($this->action);
        $response = ($this->editAction)($request, clone $data);

        if ($response instanceof RedirectActionResponse) {
            return $this->updateRedirectAction($response);
        }

        return $response;
    }
}
