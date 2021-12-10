<?php

namespace Softspring\CrudlBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Softspring\CrudlBundle\Controller\CrudlController;
use Softspring\CrudlBundle\Tests\Manager\ExampleManagerCrudl;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormFactory;

abstract class AbstractCrudlControllerTestCase extends TestCase
{
    /**
     * @var MockObject|ExampleManagerCrudl
     */
    protected $manager;

    /**
     * @var MockObject|EntityRepository
     */
    protected $repository;

    /**
     * @var MockObject|EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var MockObject|Container
     */
    protected $container;

    /**
     * @var MockObject|FormFactory
     */
    protected $formFactory;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->manager = $this->getMockBuilder(ExampleManagerCrudl::class)->disableOriginalConstructor()->getMock();

        $this->repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->manager->expects($this->any())->method('getRepository')->willReturn($this->repository);

        $this->dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->container = $this->getMockBuilder(Container::class)->disableOriginalConstructor()->getMock();
        $this->formFactory = $this->getMockBuilder(FormFactory::class)->disableOriginalConstructor()->getMock();

        $test = $this;
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($service) use ($test) {
                switch ($service) {
                    case 'event_dispatcher':
                        return $test->dispatcher;

                    case 'form.factory':
                        return $test->formFactory;

                    return null;
                }
            }))
        ;
    }

    /**
     * @param null $listFilterForm
     * @param null $createForm
     * @param null $updateForm
     * @param null $deleteForm
     *
     * @return MockObject|CrudlController
     */
    protected function getControllerMock(array $config, array $onlyMethods = [], $listFilterForm = null, $createForm = null, $updateForm = null, $deleteForm = null)
    {
        $controller = $this->getMockBuilder(CrudlController::class)
            ->setConstructorArgs([$this->manager, $listFilterForm, $createForm, $updateForm, $deleteForm, $config])
            ->onlyMethods($onlyMethods)
            ->getMock();

        $controller->setContainer($this->container);

        return $controller;
    }
}
