<?php

declare(strict_types=1);

namespace Softspring\CrudlBundle\Tests\Unit\DependencyInjection\CompilerPass;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Softspring\Component\CrudlController\Manager\DefaultCrudlEntityManager;
use Softspring\CrudlBundle\Controller\CrudlController;
use Softspring\CrudlBundle\DependencyInjection\CompilerPass\AddControllersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class AddControllersPassTest extends TestCase
{
    public function testCreatesControllerUsingConfiguredEntityManager(): void
    {
        $container = $this->createContainer([
            'products' => [
                'entity_manager' => 'app.product_manager',
                'default_view_path' => 'admin/products',
                'default_entity_attribute' => 'product',
                'actions' => [
                    'create' => ['action' => 'create'],
                ],
            ],
        ]);
        $container->setDefinition('app.form_type', new Definition());
        $container->setDefinition('app.product_manager', new Definition());

        (new AddControllersPass())->process($container);

        $definition = $container->getDefinition('sfs_crudl.controller.products');
        $configs = $definition->getArgument('$configs');

        self::assertSame(CrudlController::class, $definition->getClass());
        self::assertTrue($definition->isPublic());
        self::assertEquals(new Reference('app.product_manager'), $definition->getArgument('$manager'));
        self::assertSame('admin/products/create.html.twig', $configs['create']['view']);
        self::assertSame('product', $configs['create']['entity_attribute']);
        self::assertSame('sfs_crudl.products.create.initialize', $configs['create']['initialize_event_name']);
    }

    public function testCreatesDefaultEntityManagerForEntityClass(): void
    {
        $container = $this->createContainer([
            'products' => [
                'entity_class' => ProductEntity::class,
                'actions' => [
                    'list' => ['action' => 'list'],
                ],
            ],
        ]);

        (new AddControllersPass())->process($container);

        $manager = $container->getDefinition('sfs_crudl.entity_manager.products');

        self::assertSame(DefaultCrudlEntityManager::class, $manager->getClass());
        self::assertSame(ProductEntity::class, $manager->getArgument('$targetClass'));
        self::assertEquals(new Reference('sfs_crudl.entity_manager.products'), $container->getDefinition('sfs_crudl.controller.products')->getArgument('$manager'));
    }

    public function testReplacesExistingServiceReferencesInsideActionConfig(): void
    {
        $container = $this->createContainer([
            'products' => [
                'entity_manager' => 'app.product_manager',
                'actions' => [
                    'create' => [
                        'action' => 'create',
                        'form' => '@app.form_type',
                    ],
                ],
            ],
        ]);
        $container->setDefinition('app.product_manager', new Definition());
        $container->setDefinition('app.form_type', new Definition());

        (new AddControllersPass())->process($container);

        self::assertEquals(new Reference('app.form_type'), $container->getDefinition('sfs_crudl.controller.products')->getArgument('$configs')['create']['form']);
    }

    public function testFailsWhenControllerHasNoManagerOrEntityClass(): void
    {
        $container = $this->createContainer([
            'products' => [
                'actions' => [
                    'list' => ['action' => 'list'],
                ],
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Crudl controller must have a entity_manager or entity_class');

        (new AddControllersPass())->process($container);
    }

    private function createContainer(array $controllers): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('sfs_crudl.controllers', $controllers);

        return $container;
    }
}

final class ProductEntity
{
}
