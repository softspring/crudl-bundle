<?php

namespace Softspring\CrudlBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Trait CrudlEntityManagerTrait.
 */
trait CrudlEntityManagerTrait
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    abstract public function getTargetClass(): string;

    public function getEntityClass(): string
    {
        return $this->getEntityClassReflection()->name;
    }

    public function getEntityClassReflection(): \ReflectionClass
    {
        $metadata = $this->em->getClassMetadata($this->getTargetClass());

        return $metadata->getReflectionClass();
    }

    public function getRepository(): EntityRepository
    {
        /** @var EntityRepository $repo */
        $repo = $this->em->getRepository($this->getTargetClass());

        return $repo;
    }

    /**
     * @return object
     */
    public function createEntity()
    {
        $class = $this->getEntityClass();

        return new $class();
    }

    /**
     * @param object $entity
     */
    public function saveEntity($entity): void
    {
        if (!$this->getEntityClassReflection()->isInstance($entity)) {
            throw new \InvalidArgumentException(sprintf('$entity must be an instance of %s', $this->getEntityClass()));
        }

        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * @param object $entity
     */
    public function deleteEntity($entity): void
    {
        if (!$this->getEntityClassReflection()->isInstance($entity)) {
            throw new \InvalidArgumentException(sprintf('$entity must be an instance of %s', $this->getEntityClass()));
        }

        $this->em->remove($entity);
        $this->em->flush();
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }
}
