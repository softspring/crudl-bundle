<?php

namespace Softspring\CrudlBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Softspring\CrudlBundle\Event\FormEvent;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class FormEventTest extends TestCase
{
    public function testGetRequest()
    {
        $form = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();

        $request = new Request();
        $event = new FormEvent($form, $request);
        $this->assertEquals($request, $event->getRequest());
    }

    public function testGetForm()
    {
        $form = $this->getMockBuilder(Form::class)->disableOriginalConstructor()->getMock();
        $request = new Request();
        $event = new FormEvent($form, $request);
        $this->assertEquals($form, $event->getForm());
    }
}
