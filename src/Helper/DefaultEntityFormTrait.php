<?php

namespace Softspring\CrudlBundle\Helper;

use Softspring\Component\CrudlController\Event\FormPrepareEvent;
use Softspring\CrudlBundle\Form\DefaultEntityForm;
use Symfony\Component\Form\FormInterface;

trait DefaultEntityFormTrait
{
    protected function createDefaultEntityForm(FormPrepareEvent $formPrepareEvent, mixed $type): FormInterface
    {
        $data = $formPrepareEvent->getData();
        $options = $formPrepareEvent->getFormOptions();
        $options['manager'] = $this->manager;

        $this->form = $this->formFactory->create(DefaultEntityForm::class, $data, $options);

        $this->form->handleRequest($this->request);

        return $this->form;
    }
}