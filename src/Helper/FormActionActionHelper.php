<?php

namespace Softspring\CrudlBundle\Helper;

use Softspring\Component\CrudlController\Event\FormPrepareEvent;
use Softspring\Component\CrudlController\Helper\FormActionActionHelper as BaseFormActionActionHelper;
use Softspring\CrudlBundle\Form\DefaultEntityForm;
use Symfony\Component\Form\FormInterface;

class FormActionActionHelper extends BaseFormActionActionHelper
{
    use DefaultEntityFormTrait;

    public function createForm(FormPrepareEvent $formPrepareEvent): FormInterface
    {
        $type = $formPrepareEvent->getType();

        if (is_string($type) && DefaultEntityForm::class !== $type) {
            return parent::createForm($formPrepareEvent);
        }

        return $this->createDefaultEntityForm($formPrepareEvent, $type);
    }
}
