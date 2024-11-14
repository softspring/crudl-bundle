<?php

namespace Softspring\CrudlBundle\Helper;

use Softspring\Component\CrudlController\Event\FormPrepareEvent;
use Softspring\Component\CrudlController\Helper\TransitionActionHelper as BaseTransitionActionHelper;
use Softspring\CrudlBundle\Form\DefaultEntityForm;
use Symfony\Component\Form\FormInterface;

class TransitionActionHelper extends BaseTransitionActionHelper
{
    use DefaultEntityFormTrait;

    public function createForm(FormPrepareEvent $formPrepareEvent): FormInterface|false
    {
        $type = $formPrepareEvent->getType();

        if (!$type) {
            return false;
        }

        if (is_string($type) && DefaultEntityForm::class !== $type) {
            return parent::createForm($formPrepareEvent);
        }

        return $this->createDefaultEntityForm($formPrepareEvent, $type);
    }
}
