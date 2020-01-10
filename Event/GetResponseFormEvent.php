<?php

namespace Softspring\CrudlBundle\Event;

use Softspring\CoreBundle\Event\GetResponseEventInterface;
use Softspring\CoreBundle\Event\GetResponseTrait;

class GetResponseFormEvent extends FormEvent implements GetResponseEventInterface
{
    use GetResponseTrait;
}