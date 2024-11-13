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
            $controllerConfig = $this->fixEventsConfiguration($controllerConfig, $controllerName);
            $this->addController($controllerName, $controllerConfig, $container);
        }
    }

    protected function fixEventsConfiguration(array $controllerConfig, string $controllerName): array
    {
        foreach ($controllerConfig['actions'] as $actionName => &$actionConfig) {
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
