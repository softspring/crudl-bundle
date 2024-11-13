<?php

namespace Softspring\CrudlBundle\DependencyInjection\CompilerPass;

use InvalidArgumentException;
use Softspring\Component\CrudlController\Manager\DefaultCrudlEntityManager;
use Softspring\CrudlBundle\Controller\CrudlController;
use Softspring\CrudlBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddControllersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $controllers = $container->getParameter('sfs_crudl.controllers');

        foreach ($controllers as $controllerName => $controllerConfig) {
            $controllerConfig = $this->fixConfiguration($controllerConfig, $controllerName);
            $this->addController($controllerName, $controllerConfig, $container);
        }
    }

    protected function fixConfiguration(array $controllerConfig, string $controllerName): array
    {
        foreach ($controllerConfig['actions'] as $actionName => &$actionConfig) {
            $actionProperties = match ($actionConfig['action']) {
                'list' => Configuration::LIST_ACTION_CONFIG_KEYS,
                'create' => Configuration::CREATE_ACTION_CONFIG_KEYS,
                'update' => Configuration::UPDATE_ACTION_CONFIG_KEYS,
                'transition' => Configuration::TRANSITION_ACTION_CONFIG_KEYS,
                'read' => Configuration::READ_ACTION_CONFIG_KEYS,
                'delete' => Configuration::DELETE_ACTION_CONFIG_KEYS,
                'apply' => Configuration::APPLY_ACTION_CONFIG_KEYS,
                default => [],
            };

            // if action supports view and view is not defined, try to set a default view
            if (in_array('view', $actionProperties) && empty($actionConfig['view'])) {
                // if controller has a default_view_path, use it
                if (!empty($controllerConfig['default_view_path'])) {
                    $actionConfig['view'] = "{$controllerConfig['default_view_path']}/$actionName.html.twig";
                } else {
                    // if not, use the default bundle view for this kind of action
                    $actionConfig['view'] = "@SfsCrudl/crudl/{$actionConfig['action']}.html.twig";
                }
            }

            if (in_array('entity_attribute', $actionProperties) && empty($actionConfig['entity_attribute']) && !empty($controllerConfig['default_entity_attribute'])) {
                $actionConfig['entity_attribute'] = $controllerConfig['default_entity_attribute'];
            }

            $actionEvents = match ($actionConfig['action']) {
                'list' => Configuration::LIST_ACTION_EVENT_KEYS,
                'create' => Configuration::CREATE_ACTION_EVENT_KEYS,
                'update' => Configuration::UPDATE_ACTION_EVENT_KEYS,
                'transition' => Configuration::TRANSITION_ACTION_EVENT_KEYS,
                'read' => Configuration::READ_ACTION_EVENT_KEYS,
                'delete' => Configuration::DELETE_ACTION_EVENT_KEYS,
                'apply' => Configuration::APPLY_ACTION_EVENT_KEYS,
                default => [],
            };

            foreach ($actionEvents as $eventName) {
                if (!isset($actionConfig[$eventName])) {
                    $actionConfig[$eventName] = "sfs_crudl.$controllerName.$actionName.".str_replace('_event_name', '', $eventName);
                }
            }
        }

        return $controllerConfig;
    }

    protected function addController(string $controllerName, array $config, ContainerBuilder $container): void
    {
        $definition = new Definition(CrudlController::class);
        $definition->setPublic(true);
        $definition->addTag('controller.service_arguments');

        if ($config['entity_manager'] ?? false) {
            $definition->setArgument('$manager', new Reference($config['entity_manager']));
        } elseif ($config['entity_class'] ?? false) {
            $entityManager = new Definition(DefaultCrudlEntityManager::class);
            $entityManager->setArgument('$targetClass', $config['entity_class']);
            $entityManager->setArgument('$em', new Reference('doctrine.orm.entity_manager'));
            $container->addDefinitions(["sfs_crudl.entity_manager.$controllerName" => $entityManager]);
            $definition->setArgument('$manager', new Reference("sfs_crudl.entity_manager.$controllerName"));
        } else {
            throw new InvalidArgumentException('Crudl controller must have a entity_manager or entity_class');
        }
        $definition->setArgument('$eventDispatcher', new Reference('event_dispatcher'));
        $definition->setArgument('$twig', new Reference('twig'));
        $definition->setArgument('$formFactory', new Reference('form.factory'));
        $definition->setArgument('$authorizationChecker', new Reference('security.authorization_checker'));
        $definition->setArgument('$router', new Reference('router.default'));
        $definition->setArgument('$registry', new Reference('workflow.registry', ContainerInterface::NULL_ON_INVALID_REFERENCE));

        // if any config starts with @ and is a defined service, replace it with a reference
        foreach ($config['actions'] as $action => &$actionConfig) {
            foreach ($actionConfig as $key => &$value) {
                if (is_string($value) && strpos($value, '@') === 0) {
                    if ($container->hasDefinition(substr($value, 1))) {
                        $value = new Reference(substr($value, 1));
                    }
                }
            }
        }

        $definition->setArgument('$configs', $config['actions']);
        $container->addDefinitions(["sfs_crudl.controller.$controllerName" => $definition]);
    }
}
