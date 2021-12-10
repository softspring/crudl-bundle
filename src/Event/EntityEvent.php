<?php

namespace Softspring\CrudlBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class EntityEvent extends Event
{
    /**
     * @var object
     */
    protected $entity;

    /**
     * @var Request|null
     */
    protected $request;

    /**
     * AccountEvent constructor.
     *
     * @param object $entity
     */
    public function __construct($entity, ?Request $request)
    {
        $this->entity = $entity;
        $this->request = $request;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }
}
