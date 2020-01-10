<?php

namespace Softspring\CrudlBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Softspring\CrudlBundle\Event\ViewEvent;

class ViewEventTest extends TestCase
{
    public function testGetRequest()
    {
        $data = new \ArrayObject(['test'=>1]);
        $event = new ViewEvent($data);
        $this->assertEquals($data, $event->getData());
    }
}
