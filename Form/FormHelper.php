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

namespace Sidus\AdminBundle\Form;

use Sidus\AdminBundle\Model\Action;
use Sidus\AdminBundle\Routing\RoutingHelper;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

/**
 * Provides a simple way to access form utilities from a controller or an action
 */
class FormHelper
{
    public function __construct(
        protected RoutingHelper $routingHelper,
        protected FormFactoryInterface $formFactory,
    ) {
    }

    public function getForm(Action $action, Request $request, mixed $data, array $options = []): FormInterface
    {
        $defaultOptions = $this->getDefaultFormOptions($action, $request);

        return $this->getFormBuilder($action, $data, array_merge($defaultOptions, $options))->getForm();
    }

    public function getEmptyForm(
        Action $action,
        Request $request,
    ): FormInterface {
        $formOptions = $this->getDefaultFormOptions($action, $request);

        return $this->formFactory->createNamedBuilder(
            "form_{$action->getAdmin()->getCode()}_{$action->getCode()}",
            FormType::class,
            null,
            $formOptions
        )->getForm();
    }

    public function getFormBuilder(Action $action, mixed $data, array $options = []): FormBuilderInterface
    {
        if (!$action->getFormType()) {
            throw new UnexpectedValueException("Missing parameter 'form_type' for action '{$action->getCode()}'");
        }

        return $this->formFactory->createNamedBuilder(
            "form_{$action->getAdmin()->getCode()}_{$action->getCode()}",
            $action->getFormType(),
            $data,
            $options
        );
    }

    public function getDefaultFormOptions(Action $action, Request $request): array
    {
        return array_merge(
            $action->getFormOptions(),
            [
                'action' => $this->routingHelper->getCurrentUri($action, $request),
                'attr' => [
                    'novalidate' => 'novalidate',
                    'id' => "form_{$action->getAdmin()->getCode()}_{$action->getCode()}",
                ],
                'method' => 'post',
            ]
        );
    }
}
