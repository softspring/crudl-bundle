<?php

namespace Softspring\CrudlBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

trait CrudlEntityManagerTrait
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @return string
     *
     * @deprecated use getTargetClass
     */
    public function getClass(): string
    {
        return $this->getTargetClass();
    }

    /**
     * @return string
     */
    abstract public function getTargetClass(): string;

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->getEntityClassReflection()->name;
    }

    /**
     * @return \ReflectionClass
     */
    public function getEntityClassReflection(): \ReflectionClass
    {
        $metadata = $this->em->getClassMetadata($this->getTargetClass());
        return $metadata->getReflectionClass();
    }

    /**
     * @return EntityRepository
     */
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
        return new $class;
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
}