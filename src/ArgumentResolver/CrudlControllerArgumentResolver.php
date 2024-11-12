<?php

namespace Softspring\CrudlBundle\ArgumentResolver;

use Psr\Container\ContainerInterface;
use Softspring\CrudlBundle\Controller\CrudlController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CrudlControllerArgumentResolver implements ValueResolverInterface
{
    public function __construct(
        protected ContainerInterface $container,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $controller = $request->attributes->get('_controller');

        if (!is_string($controller) || '' === $controller) {
            return [];
        }

        $controller = explode('::', $controller);

        if (2 !== count($controller)) {
            return [];
        }

        $action = $controller[1];
        $controller = $controller[0];

        if (!$this->container->has($controller)) {
            return [];
        }

        $controllerObject = $this->container->get($controller);

        if (!$controllerObject instanceof CrudlController) {
            return [];
        }

        if (method_exists($controllerObject, $action)) {
            return [];
        }

        return [$request];
    }
}
