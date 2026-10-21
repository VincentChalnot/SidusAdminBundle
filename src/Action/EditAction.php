<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Attribute\AdminEntity;
use Sidus\AdminBundle\Doctrine\DoctrineHelper;
use Sidus\AdminBundle\Form\FormHelper;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Request\RedirectActionResponse;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
readonly class EditAction
{
    public function __construct(
        protected FormHelper $formHelper,
        protected DoctrineHelper $doctrineHelper,
        protected TemplatingHelper $templatingHelper,
    ) {
    }

    public function __invoke(
        Request $request,
        Action $action,
        #[AdminEntity]
        object $data,
    ): ActionResponseInterface {
        $form = $this->formHelper->getForm($action, $request, $data);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrineHelper->saveEntity($action, $data, $request->getSession());

            return new RedirectActionResponse(
                action: $action,
                entity: $data,
                parameters: $request->query->all(),
            );
        }

        return $this->templatingHelper->renderFormAction($action, $form, $data);
    }
}
