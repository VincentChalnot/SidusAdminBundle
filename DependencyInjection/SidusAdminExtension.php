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

namespace Sidus\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link   http://symfony.com/doc/current/cookbook/bundles/extension.html
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusAdminExtension extends Extension
{
    /** @var array */
    protected array $globalConfig;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->globalConfig = $this->processConfiguration($this->createConfiguration(), $configs);

        foreach ((array) $this->globalConfig['configurations'] as $code => $adminConfiguration) {
            $this->createAdminServiceDefinition($code, $adminConfiguration, $container);
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }

    /**
     * @return ConfigurationInterface
     */
    protected function createConfiguration(): ConfigurationInterface
    {
        return new Configuration();
    }

    protected function createAdminServiceDefinition(
        string $code,
        array $adminConfiguration,
        ContainerBuilder $container,
    ): void {
        $adminConfiguration = array_merge(['action_class' => $this->globalConfig['action_class']], $adminConfiguration);
        if (!isset($adminConfiguration['base_template'])) {
            $adminConfiguration['base_template'] = $this->globalConfig['base_template'];
        }

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
}
