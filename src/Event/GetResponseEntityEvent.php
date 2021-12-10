<?php

namespace Softspring\CrudlBundle\Event;

use Softspring\CoreBundle\Event\GetResponseEventInterface;
use Softspring\CoreBundle\Event\GetResponseTrait;

class GetResponseEntityEvent extends EntityEvent implements GetResponseEventInterface
{
    use GetResponseTrait;
}