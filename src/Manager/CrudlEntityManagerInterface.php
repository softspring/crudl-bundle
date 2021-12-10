<?php

namespace Softspring\CrudlBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Interface CrudlEntityManagerInterface.
 */
interface CrudlEntityManagerInterface
{
    public function getTargetClass(): string;

    public function getEntityClass(): string;

    public function getEntityClassReflection(): \ReflectionClass;

    public function getRepository(): EntityRepository;

    /**
     * @return object
     */
    public function createEntity();

    /**
     * @param object $entity
     */
    public function saveEntity($entity): void;

    /**
     * @param object $entity
     */
    public function deleteEntity($entity): void;

    public function getEntityManager(): EntityManagerInterface;
}
