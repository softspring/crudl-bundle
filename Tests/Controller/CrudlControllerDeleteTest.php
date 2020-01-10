<?php

namespace Softspring\CrudlBundle\Tests\Controller;

use Softspring\CrudlBundle\Controller\CrudlController;
use Softspring\CrudlBundle\Tests\Controller\Example\DeleteForm;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CrudlControllerDeleteTest extends AbstractCrudlControllerTestCase
{
    public function testDeleteEmptyConfiguration()
    {
        $controller = new CrudlController($this->manager, $this->dispatcher);

        $this->expectException(\InvalidArgumentException::class);

        $controller->delete('id', new Request());
    }

    public function testDeleteDenyUnlessGranted()
    {
        $config = [
            'delete' => [
                'is_granted' => 'ROLE_MISSING',
            ],
        ];

        $this->expectException(AccessDeniedException::class);

        $controller = $this->getControllerMock($config, ['denyAccessUnlessGranted']);
        $controller->expects($this->once())->method('denyAccessUnlessGranted')->willThrowException(new AccessDeniedException());
        $controller->delete('id', new Request());
    }

    public function testDeleteWithNotFoundEntity()
    {
        $config = [
            'delete' => [
                'initialize_event_name' => '',
            ],
        ];

        $this->repository->expects($this->once())->method('findOneBy')->willReturn(null);

        $controller = new CrudlController($this->manager, $this->dispatcher, null,null,null,null, $config);

        $this->expectException(NotFoundHttpException::class);

        $controller->delete('id', new Request());
    }

    public function testDeleteWithNoForm()
    {
        $config = [
            'delete' => [
                'initialize_event_name' => '',
            ],
        ];

        $this->repository->expects($this->once())->method('findOneBy')->willReturn(new \stdClass());

        $controller = new CrudlController($this->manager, $this->dispatcher, null,null,null,null, $config);

        $this->expectException(\InvalidArgumentException::class);

        $controller->delete('id', new Request());
    }

    public function testDeleteWithInitializeEventReturningResponse()
    {
        $config = [
            'delete' => [
                'initialize_event_name' => 'test_event',
                'view' => 'test_view.html.twig',
            ],
        ];

        $this->repository->expects($this->once())->method('findOneBy')->willReturn($entity = new \stdClass());

        $deleteForm = $this->getMockBuilder(DeleteForm::class)->getMock();

        $expectedResponse = new Response();

        $controller = $this->getControllerMock($config, ['dispatchGetResponse'], null, null, null, $deleteForm);
        $controller->expects($this->once())->method('dispatchGetResponse')->willReturn($expectedResponse);

        $response = $controller->delete('id', new Request());

        $this->assertEquals($expectedResponse, $response);
    }

    public function testDeleteWithNoSubmittedFormAndViewEvent()
    {
        $config = [
            'delete' => [
                'view' => 'test_view.html.twig',
                'view_event_name' => 'test_event',
            ],
        ];

        $this->repository->expects($this->once())->method('findOneBy')->willReturn($entity = new \stdClass());

        // assertion only one dispatch call
        $this->dispatcher->expects($this->once())->method('dispatch');

        $deleteForm = $this->getMockBuilder(DeleteForm::class)->getMock();

        $form = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();
        $this->formFactory->expects($this->once())->method('create')->willReturn($form);
        $form->expects($this->once())->method('handleRequest')->willReturn($form);

        $controller = $this->getControllerMock($config, ['renderView'], null, null, null, $deleteForm);
        $controller->expects($this->once())->method('renderView')->willReturn($config['delete']['view']);

        $response = $controller->delete('id', new Request());

        $this->assertEquals($config['delete']['view'], $response->getContent());
    }

    public function testDeleteWithFormSubmittedAndInvalidReceivingEventResponse()
    {
        $config = [
            'delete' => [
                'form_invalid_event_name' => 'test_event',
            ],
        ];

        $this->repository->expects($this->once())->method('findOneBy')->willReturn($entity = new \stdClass());

        $deleteForm = $this->getMockBuilder(DeleteForm::class)->getMock();

        $form = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();
        $this->formFactory->expects($this->once())->method('create')->willReturn($form);
        $form->expects($this->once())->method('handleRequest')->willReturn($form);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(false);

        $controller = $this->getControllerMock($config, ['dispatchGetResponse'], null, null, null, $deleteForm);
        $expectedResponse = new Response();
        $controller->expects($this->once())->method('dispatchGetResponse')->willReturn($expectedResponse);

        $response = $controller->delete('id', new Request());
        $this->assertEquals($expectedResponse, $response);
    }

    public function testDeleteWithFormSubmittedAndValidReceivingFormEventResponse()
    {
        $config = [
            'delete' => [
                'form_valid_event_name' => 'test_event',
            ],
        ];

        $this->repository->expects($this->once())->method('findOneBy')->willReturn($entity = new \stdClass());

        $deleteForm = $this->getMockBuilder(DeleteForm::class)->getMock();

        $form = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();
        $this->formFactory->expects($this->once())->method('create')->willReturn($form);
        $form->expects($this->once())->method('handleRequest')->willReturn($form);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);

        $controller = $this->getControllerMock($config, ['dispatchGetResponse'], null, null, null, $deleteForm);
        $expectedResponse = new Response();
        $controller->expects($this->once())->method('dispatchGetResponse')->willReturn($expectedResponse);

        $response = $controller->delete('id', new Request());
        $this->assertEquals($expectedResponse, $response);
    }

    public function testDeleteWithFormSubmittedAndValidReceivingSuccessEventResponse()
    {
        $config = [
            'delete' => [
                'success_event_name' => 'test_event',
            ],
        ];

        $this->repository->expects($this->once())->method('findOneBy')->willReturn($entity = new \stdClass());
        $deleteForm = $this->getMockBuilder(DeleteForm::class)->getMock();

        $form = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();
        $this->formFactory->expects($this->once())->method('create')->willReturn($form);
        $form->expects($this->once())->method('handleRequest')->willReturn($form);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);

        $controller = $this->getControllerMock($config, ['dispatchGetResponse', 'remove'], null, null, null, $deleteForm);
        $expectedResponse = new Response();
        $controller->expects($this->once())->method('dispatchGetResponse')->willReturn($expectedResponse);
        $controller->expects($this->once())->method('remove');

        $response = $controller->delete('id', new Request());
        $this->assertEquals($expectedResponse, $response);
    }

    public function testDeleteWithFormSubmittedAndValidWithRedirectRoute()
    {
        $config = [
            'delete' => [
                'success_redirect_to' => 'redirect_route',
            ],
        ];

        $this->repository->expects($this->once())->method('findOneBy')->willReturn($entity = new \stdClass());

        $deleteForm = $this->getMockBuilder(DeleteForm::class)->getMock();

        $form = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();
        $this->formFactory->expects($this->once())->method('create')->willReturn($form);
        $form->expects($this->once())->method('handleRequest')->willReturn($form);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);

        $controller = $this->getControllerMock($config, ['generateUrl', 'remove'], null, null, null, $deleteForm);
        $controller->expects($this->once())->method('generateUrl')->with($this->equalTo('redirect_route'))->willReturn('/redirect/to/route');
        $controller->expects($this->once())->method('remove');

        /** @var RedirectResponse $response */
        $response = $controller->delete('id', new Request());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/redirect/to/route', $response->getTargetUrl());
    }

    public function testDeleteWithFormSubmittedAndValidWithDefaultRedirect()
    {
        $config = [
            'delete' => [
                'success_redirect_to' => '',
            ],
        ];

        $this->repository->expects($this->once())->method('findOneBy')->willReturn($entity = new \stdClass());

        $deleteForm = $this->getMockBuilder(DeleteForm::class)->getMock();

        $form = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();
        $this->formFactory->expects($this->once())->method('create')->willReturn($form);
        $form->expects($this->once())->method('handleRequest')->willReturn($form);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);

        $controller = $this->getControllerMock($config, ['remove'], null, null, null, $deleteForm);
        $controller->expects($this->once())->method('remove');

        /** @var RedirectResponse $response */
        $response = $controller->delete('id', new Request());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getTargetUrl());
    }
}
