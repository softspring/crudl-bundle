<?php

declare(strict_types=1);

namespace Softspring\CrudlBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Softspring\CrudlBundle\DependencyInjection\SfsCrudlExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SfsCrudlExtensionTest extends TestCase
{
    public function testLoadsControllerConfigurationAndServices(): void
    {
        $container = new ContainerBuilder();

        (new SfsCrudlExtension())->load([[
            'controllers' => [
                'products' => [
                    'entity_manager' => 'app.product_manager',
                    'actions' => [
                        'list' => [],
                    ],
                ],
            ],
        ]], $container);

        self::assertTrue($container->hasParameter('sfs_crudl.controllers'));
        self::assertArrayHasKey('products', $container->getParameter('sfs_crudl.controllers'));
        self::assertTrue($container->hasDefinition('sfs_crudl.controller_argument_resolver'));
    }
}
