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

namespace Sidus\AdminBundle\Form\Type;

use Sidus\AdminBundle\Templating\ActionLinkHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Special type to create a link to an action inside a form.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ActionLinkType extends AbstractType
{
    public function __construct(
        protected ActionLinkHelper $actionLinkHelper,
    ) {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['action_link'] = $this->actionLinkHelper->getActionLink($options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->actionLinkHelper->configure($resolver);
    }

    public function getBlockPrefix(): string
    {
        return 'action_link';
    }
}
