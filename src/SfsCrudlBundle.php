<?php

namespace Softspring\CrudlBundle;

use Softspring\CrudlBundle\DependencyInjection\CompilerPass\AddControllersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SfsCrudlBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AddControllersPass());
    }
}
