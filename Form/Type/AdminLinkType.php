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

namespace Sidus\AdminBundle\Form\Type;

use LogicException;
use Sidus\AdminBundle\Admin\Action;
use Sidus\AdminBundle\Admin\Admin;
use Sidus\AdminBundle\Configuration\AdminRegistry;
use Sidus\AdminBundle\Routing\AdminRouter;
use Sidus\DataGridBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Special type to create a link inside a form.
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class AdminLinkType extends AbstractType
{
    public function __construct(
        protected AdminRouter $adminRouter,
        protected AdminRegistry $adminRegistry
    ) {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['admin'] = $options['admin'];
        $view->vars['admin_action'] = $options['admin_action'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'admin_action',
            ]
        );
        $resolver->setAllowedTypes('admin_action', ['string', Action::class]);
        $resolver->setDefaults(
            [
                'admin' => null,
            ]
        );
        $resolver->setAllowedTypes('admin', ['null', 'string', Admin::class]);
        $resolver->setNormalizer(
            'admin',
            function (Options $options, $value) {
                if ($value instanceof Admin) {
                    return $value;
                }
                if (null === $value) {
                    return $this->adminRegistry->getCurrentAdmin();
                }

                return $this->adminRegistry->getAdmin($value);
            }
        );
        $resolver->setNormalizer(
            'admin_action',
            static function (Options $options, $value) {
                /** @var Admin $admin */
                $admin = $options['admin'];

                if ($value instanceof Action) {
                    if ($value->getAdmin() !== $admin) {
                        throw new LogicException(
                            "Wrong Admin for Action {$value->getCode()}: {$value->getAdmin()->getCode()}"
                        );
                    }

                    return $value;
                }

                return $admin->getAction($value);
            }
        );
        $resolver->setNormalizer(
            'uri',
            function (Options $options, $value) {
                if (null === $value) {
                    return $this->adminRouter->generateAdminPath(
                        $options['admin'],
                        $options['admin_action']->getCode(),
                        $options['route_parameters']
                    );
                }

                return $value;
            }
        );
    }

    public function getBlockPrefix(): string
    {
        return 'admin_link';
    }

    public function getParent(): string
    {
        return LinkType::class;
    }
}
