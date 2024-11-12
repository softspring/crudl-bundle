<?php

namespace Softspring\CrudlBundle\DependencyInjection\CompilerPass;

use InvalidArgumentException;
use Softspring\Component\CrudlController\Manager\DefaultCrudlEntityManager;
use Softspring\CrudlBundle\Controller\CrudlController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddControllersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $controllers = $container->getParameter('sfs_crudl.controllers');

        foreach ($controllers as $controllerName => $controllerConfig) {
            $this->addController($controllerName, $controllerConfig, $container);
        }
    }

    protected function addController(string $name, array $config, ContainerBuilder $container): void
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
            $container->addDefinitions(["sfs_crudl.entity_manager.$name" => $entityManager]);
            $definition->setArgument('$manager', new Reference("sfs_crudl.entity_manager.$name"));
        } else {
            throw new InvalidArgumentException('Crudl controller must have a entity_manager or entity_class');
        }
        $definition->setArgument('$eventDispatcher', new Reference('event_dispatcher'));
        $definition->setArgument('$twig', new Reference('twig'));
        $definition->setArgument('$formFactory', new Reference('form.factory'));
        $definition->setArgument('$authorizationChecker', new Reference('security.authorization_checker'));
        $definition->setArgument('$router', new Reference('router.default'));
        $definition->setArgument('$configs', $config['actions']);
        $container->addDefinitions(["sfs_crudl.controller.$name" => $definition]);
    }
}
