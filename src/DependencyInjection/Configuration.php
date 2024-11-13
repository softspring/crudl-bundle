<?php

namespace Softspring\CrudlBundle\DependencyInjection;

use InvalidArgumentException;
use Softspring\Component\CrudlController\Form\DefaultDeleteForm;
use Softspring\CrudlBundle\Form\DefaultEntityForm;
use Softspring\CrudlBundle\Form\DefaultFilterForm;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const VALID_ACTIONS = ['create', 'read', 'update', 'delete', 'list', 'apply', 'transition'];

    /** @see \Softspring\Component\CrudlController\Config\ListActionConfiguration */
    public const LIST_ACTION_CONFIG_KEYS = [
        // common configuration keys
        'action', 'view', 'form', 'is_granted', 'view_data',
        // custom list configuration keys
        'view_page',
        'filter_form',
    ];

    /** @see \Softspring\Component\CrudlController\Config\ListActionConfiguration */
    public const LIST_ACTION_EVENT_KEYS = [
        'initialize_event_name',
        'filter_form_prepare_event_name',
        'filter_form_init_event_name',
        'filter_event_name',
        'view_event_name',
        'exception_event_name',
    ];

    /** @see \Softspring\Component\CrudlController\Config\CreateActionConfiguration */
    public const CREATE_ACTION_CONFIG_KEYS = [
        // common configuration keys
        'action', 'view', 'form', 'is_granted', 'view_data',
        // custom list configuration keys
        'entity_attribute',
        'success_redirect_to',
    ];

    /** @see \Softspring\Component\CrudlController\Config\CreateActionConfiguration */
    public const CREATE_ACTION_EVENT_KEYS = [
        'initialize_event_name',
        'create_entity_event_name',
        'form_prepare_event_name',
        'form_init_event_name',
        'form_valid_event_name',
        'apply_event_name',
        'success_event_name',
        'failure_event_name',
        'form_invalid_event_name',
        'view_event_name',
        'exception_event_name',
    ];

    /** @see \Softspring\Component\CrudlController\Config\UpdateActionConfiguration */
    public const UPDATE_ACTION_CONFIG_KEYS = [
        // common configuration keys
        'action', 'view', 'form', 'is_granted', 'view_data',
        // custom list configuration keys
        'entity_attribute',
        'success_redirect_to',
        'entity_attribute',
        'param_converter_key',
    ];

    /** @see \Softspring\Component\CrudlController\Config\UpdateActionConfiguration */
    public const UPDATE_ACTION_EVENT_KEYS = [
        'initialize_event_name',
        'load_entity_event_name',
        'not_found_event_name',
        'found_event_name',
        'form_prepare_event_name',
        'form_init_event_name',
        'form_valid_event_name',
        'apply_event_name',
        'success_event_name',
        'failure_event_name',
        'form_invalid_event_name',
        'view_event_name',
        'exception_event_name',
    ];

    /** @see \Softspring\Component\CrudlController\Config\DeleteActionConfiguration */
    public const DELETE_ACTION_CONFIG_KEYS = [
        // common configuration keys
        'action', 'view', 'form', 'is_granted', 'view_data',
        // custom fields
        'entity_attribute',
        'success_redirect_to',
        'entity_attribute',
        'param_converter_key',
    ];

    /** @see \Softspring\Component\CrudlController\Config\DeleteActionConfiguration */
    public const DELETE_ACTION_EVENT_KEYS = [
        'initialize_event_name',
        'load_entity_event_name',
        'not_found_event_name',
        'found_event_name',
        'form_prepare_event_name',
        'form_init_event_name',
        'form_valid_event_name',
        'apply_event_name',
        'success_event_name',
        'failure_event_name',
        'form_invalid_event_name',
        'view_event_name',
        'exception_event_name',
    ];

    /** @see \Softspring\Component\CrudlController\Config\TransitionActionConfiguration */
    public const TRANSITION_ACTION_CONFIG_KEYS = [
        // common configuration keys
        'action', 'view', 'form', 'is_granted', 'view_data',
        // custom list configuration keys
        'entity_attribute',
        'success_redirect_to',
        'entity_attribute',
        'param_converter_key',
        'transition_attribute',
        'workflow_name',
    ];

    /** @see \Softspring\Component\CrudlController\Config\TransitionActionConfiguration */
    public const TRANSITION_ACTION_EVENT_KEYS = [
        'initialize_event_name',
        'load_entity_event_name',
        'not_found_event_name',
        'found_event_name',
        'form_prepare_event_name',
        'form_init_event_name',
        'form_valid_event_name',
        'apply_event_name',
        'success_event_name',
        'failure_event_name',
        'form_invalid_event_name',
        'view_event_name',
        'exception_event_name',
    ];

    /** @see \Softspring\Component\CrudlController\Config\ReadActionConfiguration */
    public const READ_ACTION_CONFIG_KEYS = [
        // common configuration keys
        'action', 'view', 'form', 'is_granted', 'view_data',
        // custom list configuration keys
        'entity_attribute',
        'param_converter_key',
    ];

    /** @see \Softspring\Component\CrudlController\Config\ReadActionConfiguration */
    public const READ_ACTION_EVENT_KEYS = [
        'initialize_event_name',
        'load_entity_event_name',
        'not_found_event_name',
        'found_event_name',
        'view_event_name',
        'exception_event_name',
    ];

    /** @see \Softspring\Component\CrudlController\Config\ApplyActionConfiguration */
    public const APPLY_ACTION_CONFIG_KEYS = [
        // common configuration keys
        'action', 'form', 'is_granted',
        // custom list configuration keys
        'entity_attribute',
        'param_converter_key',
        'success_redirect_to',
    ];

    /** @see \Softspring\Component\CrudlController\Config\ApplyActionConfiguration */
    public const APPLY_ACTION_EVENT_KEYS = [
        'initialize_event_name',
        'load_entity_event_name',
        'not_found_event_name',
        'found_event_name',
        'apply_event_name',
        'success_event_name',
        'failure_event_name',
        'exception_event_name',
    ];

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
                foreach ($data as $configuredActionName => &$actionConfig) {
                    if (empty($actionConfig['action'])) {
                        if (in_array($configuredActionName, self::VALID_ACTIONS)) {
                            $actionConfig['action'] = $configuredActionName;
                        } else {
                            throw new InvalidArgumentException(sprintf('Crudl action "%s" must have a action', $configuredActionName));
                        }
                    }
                    $actionType = $actionConfig['action'];

                    // FILTER KEYS BY ACTION (not all keys are valid for all actions)
                    $actionConfig = array_filter($actionConfig, function ($configKey) use ($actionType) {
                        return match ($actionType) {
                            'list' => in_array($configKey, self::LIST_ACTION_CONFIG_KEYS + self::LIST_ACTION_EVENT_KEYS),
                            'create' => in_array($configKey, self::CREATE_ACTION_CONFIG_KEYS + self::CREATE_ACTION_EVENT_KEYS),
                            'update' => in_array($configKey, self::UPDATE_ACTION_CONFIG_KEYS + self::UPDATE_ACTION_EVENT_KEYS),
                            'transition' => in_array($configKey, self::TRANSITION_ACTION_CONFIG_KEYS + self::TRANSITION_ACTION_EVENT_KEYS),
                            'read' => in_array($configKey, self::READ_ACTION_CONFIG_KEYS + self::READ_ACTION_EVENT_KEYS),
                            'delete' => in_array($configKey, self::DELETE_ACTION_CONFIG_KEYS + self::DELETE_ACTION_EVENT_KEYS),
                            'apply' => in_array($configKey, self::APPLY_ACTION_CONFIG_KEYS + self::APPLY_ACTION_EVENT_KEYS),
                            default => false,
                        };
                    }, ARRAY_FILTER_USE_KEY);

                    // CONFIGURE DEFAULT VALUES FOR EACH ACTION
                    switch ($actionType) {
                        case 'list':
                            if (empty($actionConfig['view_page'])) {
                                $actionConfig['view_page'] = '@SfsCrudl/crudl/list-page.html.twig';
                            }

                            if (empty($actionConfig['filter_form'])) {
                                $actionConfig['filter_form'] = DefaultFilterForm::class;
                            }
                            break;

                        case 'create':
                            // create action, uses default entity form
                            if (empty($actionConfig['form'])) {
                                $actionConfig['form'] = DefaultEntityForm::class;
                            }
                            break;

                        case 'read':
                            // no default read options
                            break;

                        case 'update':
                            // update action, uses default entity form
                            if (empty($actionConfig['form'])) {
                                $actionConfig['form'] = DefaultEntityForm::class;
                            }
                            break;

                        case 'transition':
                            // no default transition options
                            break;

                        case 'delete':
                            // delete action, uses default delete form
                            if (empty($actionConfig['form'])) {
                                $actionConfig['form'] = DefaultDeleteForm::class;
                            }
                            break;

                        case 'apply':
                            // no default apply options
                            unset($actionConfig['view']);
                            unset($actionConfig['view_data']);
                            break;
                    }

                    // every actions but apply must have a view
                    if ($actionType != 'apply' && empty($actionConfig['view'])) {
                        $actionConfig['view'] = "@SfsCrudl/crudl/$actionType.html.twig";
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
            ->variableNode('form')->end()
            ->scalarNode('is_granted')->end()
            ->scalarNode('view_page')->end()
            ->variableNode('filter_form')->end()
            ->scalarNode('success_redirect_to')->end()
            ->scalarNode('param_converter_key')->end()
            ->scalarNode('entity_attribute')->end()

            // all events
            ->scalarNode('initialize_event_name')->end()
            ->scalarNode('create_entity_event_name')->end()
            ->scalarNode('form_prepare_event_name')->end()
            ->scalarNode('filter_event_name')->end()
            ->scalarNode('filter_form_prepare_event_name')->end()
            ->scalarNode('filter_form_init_event_name')->end()
            ->scalarNode('view_event_name')->end()
            ->scalarNode('form_init_event_name')->end()
            ->scalarNode('form_valid_event_name')->end()
            ->scalarNode('apply_event_name')->end()
            ->scalarNode('success_event_name')->end()
            ->scalarNode('failure_event_name')->end()
            ->scalarNode('form_invalid_event_name')->end()
            ->scalarNode('exception_event_name')->end()
            ->scalarNode('load_entity_event_name')->end()
            ->scalarNode('not_found_event_name')->end()
            ->scalarNode('found_event_name')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
