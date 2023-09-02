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
use Sidus\AdminBundle\Form\FormHelper;
use Sidus\AdminBundle\Request\ActionResponseInterface;
use Sidus\AdminBundle\Templating\TemplatingHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ReadAction implements ActionInjectableInterface
{
    use ActionInjectableTrait;

    public function __construct(
        protected FormHelper $formHelper,
        protected TemplatingHelper $templatingHelper
    ) {
    }

    public function __invoke(
        Request $request,
        #[AdminEntity]
        object $data,
    ): ActionResponseInterface {
        $form = $this->formHelper->getForm($this->action, $request, $data, ['disabled' => true]);

        return $this->templatingHelper->renderFormAction($this->action, $form, $data);
    }
}
