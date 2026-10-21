<?php

declare(strict_types=1);

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Attribute\AdminEntity;
use Sidus\AdminBundle\Form\FormHelper;
use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class to implement empty form actions (a confirmation button and nothing else)
 */
abstract readonly class AbstractEmptyFormAction
{
    public function __construct(
        protected FormHelper $formHelper,
        protected TemplatingHelper $templatingHelper,
    ) {
    }

    public function __invoke(
        Request $request,
        Action $action,
        #[AdminEntity]
        object $data,
    ): ActionResponseInterface {
        $form = $this->formHelper->getEmptyForm($action, $request);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->applyAction($request, $action, $form, $data);
        }

        return $this->templatingHelper->renderFormAction($action, $form, $data);
    }

    abstract protected function applyAction(
        Request $request,
        Action $action,
        FormInterface $form,
        object $data,
    ): ActionResponseInterface;
}
