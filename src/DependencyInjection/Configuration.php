<?php

namespace Softspring\CrudlBundle\DependencyInjection;

use InvalidArgumentException;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    protected const VALID_ACTIONS = ['create', 'read', 'update', 'delete', 'list', 'apply'];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sfs_crudl');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('controllers')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('entity_class')->end()
                            ->scalarNode('entity_manager')->end()
                            ->arrayNode('actions')
                                ->beforeNormalization()
                                    ->always()
                                    ->then(function ($data): array {
                                        foreach ($data as $actionName => &$actionConfig) {
                                            if (empty($actionConfig['action'])) {
                                                if (in_array($actionName, self::VALID_ACTIONS)) {
                                                    $actionConfig['action'] = $actionName;
                                                } else {
                                                    throw new InvalidArgumentException(sprintf('Crudl action "%s" must have a action', $actionName));
                                                }
                                            }
                                            $action = $actionConfig['action'];

                                            $actionConfig = array_filter($actionConfig, function ($configKey) use ($action) {
                                                if (in_array($configKey, ['action', 'view', 'form', 'is_granted', 'view_data'])) {
                                                    return true;
                                                }

                                                return match ($action) {
                                                    'list' => in_array($configKey, ['initialize_event_name', 'filter_event_name', 'view_event_name', 'view_page', 'filter_form']),
                                                    default => false,
                                                };
                                            }, ARRAY_FILTER_USE_KEY);

                                            if (empty($actionConfig['initialize_event_name'])) {
                                                $actionConfig['initialize_event_name'] = "sfs_crudl.default.$action.initialize";
                                            }

                                            switch ($action) {
                                                case 'list':
                                                    if (empty($actionConfig['view_page'])) {
                                                        $actionConfig['view_page'] = '@SfsCrudl/crudl/list-page.html.twig';
                                                    }
                                                    // no break
                                                case 'create':
                                                case 'read':
                                                case 'update':
                                                case 'delete':
                                                    if (empty($actionConfig['view'])) {
                                                        $actionConfig['view'] = "@SfsCrudl/crudl/$action.html.twig";
                                                    }
                                                    if (empty($actionConfig['view_event_name'])) {
                                                        $actionConfig['view_event_name'] = "sfs_crudl.default.$action.view";
                                                    }
                                                    break;
                                                    //                                                case 'apply':
                                                    //                                                    if (empty($actionConfig['view'])) {
                                                    //                                                        $actionConfig['view'] = '@SfsCrudl/crudl/apply.html.twig';
                                                    //                                                    }
                                                    //                                                    break;
                                            }
                                        }

                                        return $data;
                                    })
                                ->end()
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->children()
                                        // general fields
                                        ->enumNode('action')->values(self::VALID_ACTIONS)->end()
                                        ->scalarNode('view')->end()
                                        ->arrayNode('view_data')
                                            ->useAttributeAsKey('key')
                                            ->prototype('variable')->end()
                                        ->end()
                                        ->scalarNode('form')->end()
                                        ->scalarNode('is_granted')->end()

                                        // list action specific fields
                                        ->scalarNode('initialize_event_name')->end()
                                        ->scalarNode('filter_event_name')->end()
                                        ->scalarNode('view_event_name')->end()
                                        ->scalarNode('view_page')->end()
                                        ->variableNode('filter_form')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
