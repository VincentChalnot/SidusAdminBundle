<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\AdminBundle\DependencyInjection;

use Sidus\BaseBundle\DependencyInjection\SidusBaseExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link   http://symfony.com/doc/current/cookbook/bundles/extension.html
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusAdminExtension extends SidusBaseExtension
{
    /** @var array */
    protected $globalConfig;

    /**
     * {@inheritdoc}
     * @throws \Exception
     * @throws BadMethodCallException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->globalConfig = $this->processConfiguration($this->createConfiguration(), $configs);

        $container->setParameter(
            'sidus_admin.templating.fallback_template_directory',
            $this->globalConfig['fallback_template_directory']
        );

        foreach ((array) $this->globalConfig['configurations'] as $code => $adminConfiguration) {
            $this->createAdminServiceDefinition($code, $adminConfiguration, $container);
        }

        parent::load($configs, $container);
    }

    /**
     * @return Configuration
     */
    protected function createConfiguration()
    {
        return new Configuration();
    }

    /**
     * @param string           $code
     * @param array            $adminConfiguration
     * @param ContainerBuilder $container
     *
     * @throws BadMethodCallException
     */
    protected function createAdminServiceDefinition($code, array $adminConfiguration, ContainerBuilder $container)
    {
        $adminConfiguration = $this->finalizeConfiguration($code, $adminConfiguration, $container);

        $definition = new Definition(
            $this->globalConfig['admin_class'],
            [
                $code,
                $adminConfiguration,
            ]
        );
        $definition->addTag('sidus.admin');
        $definition->setPublic(false);
        $container->setDefinition('sidus_admin.admin.'.$code, $definition);
    }

    /**
     * @param string           $code
     * @param array            $adminConfiguration
     * @param ContainerBuilder $container
     *
     * @return array
     */
    protected function finalizeConfiguration($code, array $adminConfiguration, ContainerBuilder $container)
    {
        $defaultConfig = [
            'action_class' => $this->globalConfig['action_class'],
        ];

        return array_merge($defaultConfig, $adminConfiguration);
    }
}
