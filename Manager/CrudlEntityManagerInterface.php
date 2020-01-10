<?php

namespace Softspring\CrudlBundle\Manager;

use Doctrine\ORM\EntityRepository;

interface CrudlEntityManagerInterface
{
    /**
     * @return string
     *
     * @deprecated use getTargetClass
     */
    public function getClass(): string;

    /**
     * @return string
     */
    public function getTargetClass(): string;

    /**
     * @return string
     */
    public function getEntityClass(): string;

    /**
     * @return \ReflectionClass
     */
    public function getEntityClassReflection(): \ReflectionClass;

    /**
     * @return EntityRepository
     */
    public function getRepository(): EntityRepository;

    /**
     * @return object
     */
    public function createEntity();

    /**
     * @param object $entity
     */
    public function saveEntity($entity): void;
}