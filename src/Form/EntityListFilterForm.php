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
    public function getBlockPrefix()
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
        $builder->add(self::getOrderFieldParamName(), HiddenType::class, [
            'mapped' => false,
        ]);

        $builder->add(self::getOrderDirectionParamName(), HiddenType::class, [
            'mapped' => false,
        ]);

        $builder->add(self::getRppParamName(), HiddenType::class, [
            'mapped' => false,
        ]);
    }

    public function getPage(Request $request): int
    {
        return (int) $request->query->get(self::getPageParamName(), 1);
    }

    public function getRpp(Request $request): int
    {
        return (int) $request->query->get(self::getRppParamName(), 50);
    }

    public function getOrder(Request $request): array
    {
        if (class_exists(RequestParam::class)) {
            $order = RequestParam::getQueryValidParam($request, self::getOrderFieldParamName(), 'id', ['id']);
            $sort = RequestParam::getQueryValidParam($request, self::getOrderDirectionParamName(), 'asc', ['asc', 'desc']);

            return [$order => $sort];
        }

        return [$request->query->get(self::getOrderFieldParamName(), '') ?: 'id' => $request->query->get(self::getOrderDirectionParamName(), '') ?: 'asc'];
    }

    public static function getPageParamName(): string
    {
        return 'page';
    }

    public static function getRppParamName(): string
    {
        return 'rpp';
    }

    public static function getOrderFieldParamName(): string
    {
        return 'sort';
    }

    public static function getOrderDirectionParamName(): string
    {
        return 'order';
    }
}
