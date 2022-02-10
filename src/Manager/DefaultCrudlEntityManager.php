<?php

namespace Softspring\CrudlBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;

class DefaultCrudlEntityManager implements CrudlEntityManagerInterface
{
    use CrudlEntityManagerTrait;

    protected string $targetClass;

    protected EntityManagerInterface $em;

    /**
     * DefaultCrudlEntityManager constructor.
     */
    public function __construct(string $targetClass, EntityManagerInterface $em)
    {
        $this->targetClass = $targetClass;
        $this->em = $em;
    }

    public function getTargetClass(): string
    {
        return $this->targetClass;
    }
}
