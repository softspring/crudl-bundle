<?php

declare(strict_types=1);

namespace Softspring\CrudlBundle\Tests\ArgumentResolver;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Softspring\CrudlBundle\ArgumentResolver\CrudlControllerArgumentResolver;
use Softspring\CrudlBundle\Controller\CrudlController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class CrudlControllerArgumentResolverTest extends TestCase
{
    public function testResolvesRequestForDynamicCrudlActionMethods(): void
    {
        $controller = $this->createStub(CrudlController::class);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->with('crudl.controller')->willReturn(true);
        $container->method('get')->with('crudl.controller')->willReturn($controller);

        $resolver = new CrudlControllerArgumentResolver($container);
        $request = new Request();
        $request->attributes->set('_controller', 'crudl.controller::list_products');
        $metadata = new ArgumentMetadata('request', Request::class, false, false, null);

        $result = iterator_to_array($resolver->resolve($request, $metadata));

        $this->assertSame([$request], $result);
    }

    public function testSkipsRealControllerMethods(): void
    {
        $controller = $this->createStub(CrudlController::class);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->with('crudl.controller')->willReturn(true);
        $container->method('get')->with('crudl.controller')->willReturn($controller);

        $resolver = new CrudlControllerArgumentResolver($container);
        $request = new Request();
        $request->attributes->set('_controller', 'crudl.controller::__invoke');
        $metadata = new ArgumentMetadata('request', Request::class, false, false, null);

        $result = iterator_to_array($resolver->resolve($request, $metadata));

        $this->assertSame([], $result);
    }
}
