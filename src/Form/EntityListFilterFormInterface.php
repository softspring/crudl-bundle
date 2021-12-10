<?php

namespace Softspring\CrudlBundle\Form;

use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;

interface EntityListFilterFormInterface extends FormTypeInterface
{
    public function getPage(Request $request): int;

    public function getRpp(Request $request): int;

    public function getOrder(Request $request): array;

    public static function getPageParamName(): string;

    public static function getRppParamName(): string;

    public static function getOrderFieldParamName(): string;

    public static function getOrderDirectionParamName(): string;
}
