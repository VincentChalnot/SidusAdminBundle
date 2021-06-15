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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @Security("is_granted('create', _admin.getEntity())")
 */
class CreateAction implements ActionInjectableInterface
{
    use ActionInjectableTrait;
    use UpdateSubActionRedirectionTrait;

    public function __construct(
        protected EditAction $editAction,
        AuthorizationCheckerInterface $authorizationChecker,
    ) {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function __invoke(Request $request): Response
    {
        $this->updateRedirectAction($this->editAction, $this->action);
        $class = $this->action->getAdmin()->getEntity();

        return ($this->editAction)($request, new $class());
    }
}
