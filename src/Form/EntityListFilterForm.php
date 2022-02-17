<?php

namespace Softspring\CrudlBundle\Form;

use Jhg\DoctrinePaginationBundle\Request\RequestParam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityListFilterForm extends AbstractType implements EntityListFilterFormInterface
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
            'required' => false,
            'attr' => ['novalidate' => 'novalidate'],
            'allow_extra_fields' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(static::getOrderFieldParamName(), HiddenType::class, [
            'mapped' => false,
        ]);

        $builder->add(static::getOrderDirectionParamName(), HiddenType::class, [
            'mapped' => false,
        ]);

        $builder->add(static::getRppParamName(), HiddenType::class, [
            'mapped' => false,
        ]);
    }

    public function getPage(Request $request): int
    {
        return (int) ($request->query->get(static::getPageParamName()) ?: 1);
    }

    public function getRpp(Request $request): int
    {
        return (int) ($request->query->get(static::getRppParamName()) ?: static::rppDefault());
    }

    public function getOrder(Request $request): array
    {
        if (class_exists(RequestParam::class)) {
            $order = RequestParam::getQueryValidParam($request, static::getOrderFieldParamName(), static::orderDefaultField(), static::orderValidFields());
            $sort = RequestParam::getQueryValidParam($request, static::getOrderDirectionParamName(), 'asc', ['asc', 'desc']);

            return [$order => $sort];
        }

        return [$request->query->get(static::getOrderFieldParamName(), '') ?: 'id' => $request->query->get(static::getOrderDirectionParamName(), '') ?: 'asc'];
    }

    /**
     * @deprecated use pageParamName() instead
     */
    public static function getPageParamName(): string
    {
        return static::pageParamName();
    }

    public static function pageParamName(): string
    {
        return 'page';
    }

    /**
     * @deprecated use rppParamName() instead
     */
    public static function getRppParamName(): string
    {
        return static::rppParamName();
    }

    public static function rppParamName(): string
    {
        return 'rpp';
    }

    public static function rppDefault(): int
    {
        return 50;
    }

    /**
     * @deprecated use orderFieldParamName() instead
     */
    public static function getOrderFieldParamName(): string
    {
        return static::orderFieldParamName();
    }

    public static function orderFieldParamName(): string
    {
        return 'sort';
    }

    /**
     * @deprecated use orderDirectionParamName() instead
     */
    public static function getOrderDirectionParamName(): string
    {
        return static::orderDirectionParamName();
    }

    public static function orderDirectionParamName(): string
    {
        return 'order';
    }

    public static function orderValidFields(): array
    {
        return ['id'];
    }

    public static function orderDefaultField(): string
    {
        return 'id';
    }
}
