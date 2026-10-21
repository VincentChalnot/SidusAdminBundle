<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Doctrine\DoctrineHelper;
use Sidus\AdminBundle\Form\FormHelper;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Request\RedirectActionResponse;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Asks for a confirmation then deletes the entity and redirects to the action of the `redirect_action` option of the
 * action (`list` by default).
 */
#[AsController]
readonly class DeleteAction extends AbstractEmptyFormAction
{
    public function __construct(
        FormHelper $formHelper,
        TemplatingHelper $templatingHelper,
        protected DoctrineHelper $doctrineHelper,
    ) {
        parent::__construct($formHelper, $templatingHelper);
    }

    protected function applyAction(
        Request $request,
        Action $action,
        FormInterface $form,
        object $data,
    ): ActionResponseInterface {
        $this->doctrineHelper->deleteEntity($action, $data, $request->getSession());

        return new RedirectActionResponse(
            action: $action->getAdmin()->getAction($action->getOption('redirect_action', 'list')),
        );
    }
}
