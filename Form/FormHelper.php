<?php

namespace Sidus\AdminBundle\Form;

use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Routing\RoutingHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a simple way to access form utilities from a controller or an action
 */
class FormHelper
{
    /** @var RoutingHelper */
    protected $routingHelper;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param RoutingHelper        $routingHelper
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(RoutingHelper $routingHelper, FormFactoryInterface $formFactory)
    {
        $this->routingHelper = $routingHelper;
        $this->formFactory = $formFactory;
    }

    /**
     * @param Action  $action
     * @param Request $request
     * @param mixed   $data
     * @param array   $options
     *
     * @return FormInterface
     */
    public function getForm(Action $action, Request $request, $data, array $options = []): FormInterface
    {
        $dataId = $data && method_exists($data, 'getId') ? $data->getId() : null;
        $defaultOptions = $this->getDefaultFormOptions($action, $request, $dataId);

        return $this->getFormBuilder($action, $data, array_merge($defaultOptions, $options))->getForm();
    }

    /**
     * @param Action $action
     * @param mixed  $data
     * @param array  $options
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     *
     * @return FormBuilderInterface
     */
    public function getFormBuilder(Action $action, $data, array $options = []): FormBuilderInterface
    {
        if (!$action->getFormType()) {
            throw new \UnexpectedValueException("Missing parameter 'form_type' for action '{$action->getCode()}'");
        }

        return $this->formFactory->createNamedBuilder(
            "form_{$action->getAdmin()->getCode()}_{$action->getCode()}",
            $action->getFormType(),
            $data,
            $options
        );
    }

    /**
     * @param Action  $action
     * @param Request $request
     * @param null    $dataId
     *
     * @return array
     */
    public function getDefaultFormOptions(Action $action, Request $request, $dataId = null): array
    {
        $dataId = $dataId ?: 'new';

        return array_merge(
            $action->getFormOptions(),
            [
                'action' => $this->routingHelper->getCurrentUri($action, $request),
                'attr' => [
                    'novalidate' => 'novalidate',
                    'id' => "form_{$action->getAdmin()->getCode()}_{$action->getCode()}_{$dataId}",
                ],
                'method' => 'post',
            ]
        );
    }
}
