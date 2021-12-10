<?php

namespace Softspring\CrudlBundle\Form;

use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;

interface EntityListFilterFormInterface extends FormTypeInterface
{
    /**
     * @param Request $request
     * @return int
     */
    public function getPage(Request $request): int;

    /**
     * @param Request $request
     * @return int
     */
    public function getRpp(Request $request): int;

    /**
     * @param Request $request
     * @return array
     */
    public function getOrder(Request $request): array;

    /**
     * @return string
     */
    public static function getPageParamName(): string;

    /**
     * @return string
     */
    public static function getRppParamName(): string;

    /**
     * @return string
     */
    public static function getOrderFieldParamName(): string;

    /**
     * @return string
     */
    public static function getOrderDirectionParamName(): string;
}