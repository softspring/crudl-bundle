<?php

namespace Softspring\CrudlBundle\Tests\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Softspring\CrudlBundle\Manager\CrudlEntityManagerInterface;
use Softspring\CrudlBundle\Manager\CrudlEntityManagerTrait;

class ExampleManagerCrudl implements CrudlEntityManagerInterface
{
    use CrudlEntityManagerTrait;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getTargetClass(): string
    {
        return 'Softspring\\CrudlBundle\\Tests\\Manager\\ExampleEntity';
    }
}
