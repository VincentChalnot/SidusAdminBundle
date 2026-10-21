<?php

declare(strict_types=1);

namespace Sidus\AdminBundle;

use Sidus\AdminBundle\DependencyInjection\AdminDefinitionPass;
use Sidus\AdminBundle\DependencyInjection\AdminConfigurationResolver;
use Sidus\AdminBundle\Templating\ActionLinkHelper;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
final class SidusAdminBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->enumNode('theme')
                    ->values([null, ...AdminConfigurationResolver::THEMES])
                    ->defaultNull()
                    ->info('Opt-in set of action templates: null (default) is neutral, framework-agnostic HTML. Available: '.implode(', ', AdminConfigurationResolver::THEMES))
                ->end()
                ->scalarNode('base_template')
                    ->defaultNull()
                    ->info('The layout of all the actions, it must define the "container" block. Defaults to "'.AdminConfigurationResolver::DEFAULT_BASE_TEMPLATE.'" (the one of the theme when there is one)')
                ->end()
                ->arrayNode('action_link')
                    ->info('Default options of action_link() and of the action_link form type')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('icon')->defaultFalse()->info('Render an icon with the label by default')->end()
                        ->scalarNode('icon_template')->defaultValue('<span data-icon="{icon}" aria-hidden="true"></span>')->info('Raw HTML, {icon} is replaced by the icon name')->end()
                        ->scalarNode('class')->defaultNull()->info('CSS class of the link')->end()
                    ->end()
                ->end()
                ->arrayNode('configurations')
                    ->info('The admins by code')
                    ->useAttributeAsKey('code')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->performNoDeepMerging()
                        ->children()
                            ->scalarNode('entity')->isRequired()->cannotBeEmpty()->info('Class (or interface) name of the entity')->end()
                            ->scalarNode('prefix')->isRequired()->cannotBeEmpty()->info('Routing prefix of all the actions')->end()
                            ->arrayNode('controller_pattern')
                                ->info('Controller references the actions without _controller are resolved with, the first existing one wins. Variables: {{admin}}, {{Admin}}, {{action}}, {{Action}}')
                                ->beforeNormalization()->castToArray()->end()
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('template_pattern')
                                ->info('Template names the actions are rendered with, the first existing one wins. Variables: {{admin}}, {{Admin}}, {{action}}, {{Action}}, {{format}}')
                                ->beforeNormalization()->castToArray()->end()
                                ->scalarPrototype()->end()
                            ->end()
                            ->scalarNode('base_template')->defaultNull()->info('Overrides the global base_template for this admin')->end()
                            ->scalarNode('form_type')->defaultNull()->info('Form type class of the actions without form_type')->end()
                            ->variableNode('options')->defaultValue([])->info('Anything you need, read with Admin::getOption()')
                                ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The options must be a map.')->end()
                            ->end()
                            ->arrayNode('permissions')
                                ->info('Security attributes required for the whole admin (all of them)')
                                ->beforeNormalization()->castToArray()->end()
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('actions')
                                ->info('The actions by code')
                                ->useAttributeAsKey('code')
                                ->normalizeKeys(false)
                                ->arrayPrototype()
                                    ->performNoDeepMerging()
                                    ->children()
                                        ->scalarNode('path')->isRequired()->cannotBeEmpty()->info('Route path, relative to the prefix of the admin, starting with a slash')->end()
                                        ->scalarNode('form_type')->defaultNull()->info('Defaults to the form type of the admin')->end()
                                        ->variableNode('form_options')->defaultValue([])->info('Static form options')
                                            ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The form options must be a map.')->end()
                                        ->end()
                                        ->scalarNode('base_template')->defaultNull()->info('Defaults to the one of the admin')->end()
                                        ->scalarNode('template')->defaultNull()->info('Fixed template, the template_pattern of the admin is used when null')->end()
                                        ->variableNode('template_parameters')->defaultValue([])->info('Extra variables of the template')
                                            ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The template parameters must be a map.')->end()
                                        ->end()
                                        ->arrayNode('permissions')
                                            ->info('Security attributes required for this action, on top of the ones of the admin (all of them)')
                                            ->beforeNormalization()->castToArray()->end()
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->variableNode('options')->defaultValue([])->info('Anything you need, read with Action::getOption(). Not related to the route, see route_options')
                                            ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The options must be a map.')->end()
                                        ->end()
                                        ->variableNode('defaults')->defaultValue([])->info('Route defaults, e.g. _controller or _controller_pattern (overrides the one of the admin)')
                                            ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The route defaults must be a map.')->end()
                                        ->end()
                                        ->variableNode('requirements')->defaultValue([])->info('Route requirements')
                                            ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The route requirements must be a map.')->end()
                                        ->end()
                                        ->variableNode('route_options')->defaultValue([])->info('Route options')
                                            ->validate()->ifTrue(static fn (mixed $v): bool => !\is_array($v))->thenInvalid('The route options must be a map.')->end()
                                        ->end()
                                        ->scalarNode('host')->defaultValue('')->end()
                                        ->arrayNode('schemes')
                                            ->beforeNormalization()->castToArray()->end()
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->arrayNode('methods')
                                            ->beforeNormalization()->castToArray()->end()
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->scalarNode('condition')->defaultNull()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
        $builder->getDefinition(ActionLinkHelper::class)->setArgument('$defaultOptions', $config['action_link']);

        // The admins are built and validated once everything is registered, see AdminDefinitionPass
        $container->parameters()->set(AdminDefinitionPass::RAW_PARAMETER, [
            'theme' => $config['theme'],
            'base_template' => $config['base_template'],
            'configurations' => $config['configurations'],
        ]);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AdminDefinitionPass());
    }
}
