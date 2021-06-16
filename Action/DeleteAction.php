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
use Sidus\AdminBundle\Doctrine\DoctrineHelper;
use Sidus\AdminBundle\Form\FormHelper;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Request\RedirectActionResponse;
use Sidus\AdminBundle\Routing\RoutingHelper;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Security("is_granted('delete', data)")
 */
class DeleteAction extends AbstractEmptyFormAction
{
    public function __construct(
        FormHelper $formHelper,
        TemplatingHelper $templatingHelper,
        protected RoutingHelper $routingHelper,
        protected DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($formHelper, $templatingHelper);
    }

    protected function applyAction(Request $request, FormInterface $form, $data): ActionResponseInterface
    {
        $this->doctrineHelper->deleteEntity($this->action, $data, $request->getSession());

        return new RedirectActionResponse(
            action: $this->action->getAdmin()->getAction(
                $this->action->getOption('redirect_action', 'list')
            ),
        );
    }
}
