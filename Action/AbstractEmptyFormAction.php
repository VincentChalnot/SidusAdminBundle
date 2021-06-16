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

use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sidus\AdminBundle\Form\FormHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class to implement empty form actions
 */
abstract class AbstractEmptyFormAction implements ActionInjectableInterface
{
    use ActionInjectableTrait;

    public function __construct(
        protected FormHelper $formHelper,
        protected TemplatingHelper $templatingHelper
    ) {
    }

    /**
     * @ParamConverter(name="data", converter="sidus_admin.entity")
     */
    public function __invoke(Request $request, object $data): ActionResponseInterface
    {
        $form = $this->formHelper->getEmptyForm($this->action, $request);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->applyAction($request, $form, $data);
        }

        return $this->templatingHelper->renderFormAction(
            $this->action,
            $form,
            $data
        );
    }

    abstract protected function applyAction(Request $request, FormInterface $form, object $data): ActionResponseInterface;
}
