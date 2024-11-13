<?php

namespace Softspring\CrudlBundle\Helper;

use Softspring\Component\CrudlController\Event\FormPrepareEvent;
use Softspring\Component\CrudlController\Helper\FormActionActionHelper as BaseFormActionActionHelper;
use Softspring\CrudlBundle\Form\DefaultEntityForm;
use Symfony\Component\Form\FormInterface;

class FormActionActionHelper extends BaseFormActionActionHelper
{
    use DefaultEntityFormTrait;

    public function dispatchFormPrepare(array $options = null): FormPrepareEvent
    {
        if (null === $options) {
            $options = [
                'method' => 'POST',
            ];

            if (is_array($this->config['form'])) {
                $options = array_merge($options, $this->config['form']);
            }
        }

        return parent::dispatchFormPrepare($options);
    }

    public function createForm(FormPrepareEvent $formPrepareEvent): FormInterface
    {
        $type = $formPrepareEvent->getType();

        if (is_string($type) && DefaultEntityForm::class !== $type) {
            return parent::createForm($formPrepareEvent);
        }

        return $this->createDefaultEntityForm($formPrepareEvent, $type);
    }
}
