<?php

namespace Softspring\CrudlBundle\Tests\Controller\Example;

use Softspring\CrudlBundle\Form\EntityDeleteFormInterface;
use Symfony\Component\Form\AbstractType;

class DeleteForm extends AbstractType implements EntityDeleteFormInterface
{
}
