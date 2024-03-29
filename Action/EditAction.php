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
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Sidus\AdminBundle\Doctrine\DoctrineHelper;
use Sidus\AdminBundle\Form\FormHelper;
use Sidus\AdminBundle\Routing\RoutingHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class EditAction implements ActionInjectableInterface
{
    use ActionInjectableTrait;

    public function __construct(
        protected FormHelper $formHelper,
        protected DoctrineHelper $doctrineHelper,
        protected RoutingHelper $routingHelper,
        protected TemplatingHelper $templatingHelper
    ) {
    }

    public function __invoke(
        Request $request,
        #[AdminEntity]
        object $data,
    ): ActionResponseInterface {
        $form = $this->formHelper->getForm($this->action, $request, $data);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrineHelper->saveEntity($this->action, $data, $request->getSession());

            return new RedirectActionResponse(
                action: $this->action,
                entity: $data,
                parameters: $request->query->all(),
            );
        }

        return $this->templatingHelper->renderFormAction($this->action, $form, $data);
    }
}
