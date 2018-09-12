<?php

namespace Sidus\AdminBundle\Action;

use Sidus\AdminBundle\Templating\TemplatingHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Form\FormHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('read', data)")
 */
class ReadAction implements ActionInjectableInterface
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
        $form = $this->formHelper->getForm($this->action, $request, $data, ['disabled' => true]);

        return $this->templatingHelper->renderFormAction($this->action, $form, $data);
    }

    /**
     * @param Action $action
     */
    public function setAction(Action $action): void
    {
        $this->action = $action;
    }
}
