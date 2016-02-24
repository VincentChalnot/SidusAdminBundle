<?php

namespace Sidus\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /** @var string */
    protected $root;

    /**
     * Configuration constructor.
     * @param string $root
     */
    public function __construct($root = 'sidus_admin')
    {
        $this->root = $root;
    }


    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->root);

        $rootNode
            ->children()
                ->scalarNode('admin_class')->defaultValue('Sidus\AdminBundle\Admin\Admin')->end()
                ->scalarNode('action_class')->defaultValue('Sidus\AdminBundle\Admin\Action')->end()
                ->scalarNode('fallback_template')->defaultNull()->end()
                ->append($this->getAdminConfigTreeBuilder())
            ->end();

        return $treeBuilder;
    }

    /**
     * @return NodeDefinition
     * @throws \RuntimeException
     */
    protected function getAdminConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('configurations');
        $adminDefinition = $node
            ->useAttributeAsKey('code')
            ->prototype('array')
                ->performNoDeepMerging()
                ->children();

        $this->appendAdminDefinition($adminDefinition);

        $adminDefinition->end()
                ->end()
            ->end();
        return $node;
    }

    /**
     * @param NodeBuilder $adminDefinition
     */
    protected function appendAdminDefinition(NodeBuilder $adminDefinition)
    {
        $actionDefinition = $adminDefinition
            ->scalarNode('controller')->isRequired()->end()
            ->scalarNode('prefix')->isRequired()->end()
            ->scalarNode('entity')->isRequired()->end()
            ->scalarNode('action_class')->end()
            ->scalarNode('default_form_type')->defaultNull()->end()
            ->scalarNode('base_template')->defaultNull()->end()
            ->variableNode('options')->defaultValue([])->end()
            ->arrayNode('actions')
                ->useAttributeAsKey('code')
                ->prototype('array')
                    ->performNoDeepMerging()
                    ->children();

        $this->appendActionDefinition($actionDefinition);

        $actionDefinition->end()
                    ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $actionDefinition
     */
    protected function appendActionDefinition(NodeBuilder $actionDefinition)
    {
        $actionDefinition
            // Custom parameters
            ->scalarNode('form_type')->defaultNull()->end()
            ->scalarNode('template')->defaultNull()->end()

            // Default route parameters
            ->scalarNode('path')->isRequired()->end()
            ->scalarNode('defaults')->defaultValue([])->end()
            ->scalarNode('requirements')->defaultValue([])->end()
            ->scalarNode('options')->defaultValue([])->end()
            ->scalarNode('host')->defaultValue('')->end()
            ->scalarNode('schemes')->defaultValue([])->end()
            ->scalarNode('methods')->defaultValue([])->end()
            ->scalarNode('condition')->defaultNull()->end();
    }
}
