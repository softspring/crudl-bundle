<?php

declare(strict_types=1);

namespace Softspring\CrudlBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Softspring\CrudlBundle\DependencyInjection\Configuration;
use Softspring\CrudlBundle\Form\DefaultFilterForm;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testListActionKeepsCustomEventNamesAndAddsDefaults(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, [[
            'controllers' => [
                'products' => [
                    'entity_manager' => 'App\\Manager\\ProductManager',
                    'actions' => [
                        'list_products' => [
                            'action' => 'list',
                            'view_event_name' => 'app.products.list.view',
                        ],
                    ],
                ],
            ],
        ]]);

        $action = $config['controllers']['products']['actions']['list_products'];

        $this->assertSame('list', $action['action']);
        $this->assertSame('app.products.list.view', $action['view_event_name']);
        $this->assertSame(DefaultFilterForm::class, $action['filter_form']);
        $this->assertSame('@SfsCrudl/crudl/list-page.html.twig', $action['view_page']);
    }

    public function testTransitionActionAcceptsWorkflowKeys(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, [[
            'controllers' => [
                'orders' => [
                    'entity_manager' => 'App\\Manager\\OrderManager',
                    'actions' => [
                        'approve' => [
                            'action' => 'transition',
                            'workflow_name' => 'order_flow',
                            'transition_attribute' => 'transition',
                        ],
                    ],
                ],
            ],
        ]]);

        $action = $config['controllers']['orders']['actions']['approve'];

        $this->assertSame('order_flow', $action['workflow_name']);
        $this->assertSame('transition', $action['transition_attribute']);
    }
}
