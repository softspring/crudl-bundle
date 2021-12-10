<?php

namespace Softspring\CrudlBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;

class DefaultCrudlEntityManager implements CrudlEntityManagerInterface
{
    use CrudlEntityManagerTrait;

    /**
     * @var string
     */
    protected $targetClass;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * DefaultCrudlEntityManager constructor.
     *
     * @param string                 $targetClass
     * @param EntityManagerInterface $em
     */
    public function __construct(string $targetClass, EntityManagerInterface $em)
    {
        $this->targetClass = $targetClass;
        $this->em = $em;
    }

    /**
     * @return string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }
}