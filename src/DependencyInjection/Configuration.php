<?php

namespace Softspring\CrudlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sfs_crudl');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->arrayNode('controllers')
                ->useAttributeAsKey('name')
                ->children()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('entity_manager')->end()
                            ->arrayNode('actions')
                                ->useAttributeAsKey('name')
                                ->children()
                                    ->prototype('array')
                                        ->enumNode('type')->required()->values(['create', 'read', 'update', 'delete', 'list', 'apply'])->end()
                                        ->scalarNode('view')->end()
                                        ->scalarNode('form')->end()
                                        ->scalarNode('is_granted')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}