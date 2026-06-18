<?php

declare(strict_types=1);

namespace Softspring\CrudlBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Softspring\CrudlBundle\DependencyInjection\CompilerPass\AddControllersPass;
use Softspring\CrudlBundle\SfsCrudlBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SfsCrudlBundleTest extends TestCase
{
    public function testReturnsPackagePath(): void
    {
        self::assertSame(\dirname(__DIR__, 2), (new SfsCrudlBundle())->getPath());
    }

    public function testRegistersControllerCompilerPass(): void
    {
        $container = new ContainerBuilder();

        (new SfsCrudlBundle())->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();

        self::assertTrue($this->hasCompilerPass($passes, AddControllersPass::class));
    }

    /**
     * @param object[] $passes
     */
    private function hasCompilerPass(array $passes, string $class): bool
    {
        foreach ($passes as $pass) {
            if ($pass instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
