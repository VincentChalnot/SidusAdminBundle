<?php

namespace Sidus\AdminBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link   http://symfony.com/doc/current/cookbook/bundles/extension.html
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class SidusAdminExtension extends Extension
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

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));
        $loader->load('events.yml');
        $loader->load('routing.yml');
        $loader->load('services.yml');
        $loader->load('twig.yml');
    }

    /**
     * @return Configuration
     */
    protected function createConfiguration()
    {
        return new Configuration();
    }

    /**
     * @param                  $code
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
        $container->setDefinition('sidus_admin.admin.'.$code, $definition);
    }

    /**
     * @param                  $code
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
