<?php

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Templating\TemplatingHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Form\FormHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class to implement empty form actions
 */
abstract class AbstractEmptyFormAction implements ActionInjectableInterface
{
    /** @var FormHelper */
    protected $formHelper;

    /** @var TemplatingHelper */
    protected $templatingHelper;

    /** @var Action */
    protected $action;

    /**
     * @param FormHelper       $formHelper
     * @param TemplatingHelper $templatingHelper
     */
    public function __construct(
        FormHelper $formHelper,
        TemplatingHelper $templatingHelper
    ) {
        $this->formHelper = $formHelper;
        $this->templatingHelper = $templatingHelper;
    }

    /**
     * @ParamConverter(name="data", converter="sidus_admin.entity")
     *
     * @param Request $request
     * @param mixed   $data
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function __invoke(Request $request, $data): Response
    {
        $dataId = $data->getId();
        $form = $this->formHelper->getEmptyForm($this->action, $request, $data);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->applyAction($request, $form, $data);
        }

        return $this->templatingHelper->renderFormAction(
            $this->action,
            $form,
            $data,
            [
                'dataId' => $dataId,
            ]
        );
    }

    /**
     * @param Action $action
     */
    public function setAction(Action $action): void
    {
        $this->action = $action;
    }

    /**
     * @param Request       $request
     * @param FormInterface $form
     * @param mixed         $data
     *
     * @return Response
     */
    abstract protected function applyAction(Request $request, FormInterface $form, $data): Response;
}
