<?php

namespace Softspring\CrudlBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use ReflectionProperty;
use Softspring\Component\CrudlController\Manager\CrudlEntityManagerInterface;
use Softspring\Component\DoctrinePaginator\Form\PaginatorForm;
use Softspring\Component\DynamicFormType\Form\Resolver\TypeResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultFilterForm extends PaginatorForm
{
    public function __construct(EntityManagerInterface $em, protected TypeResolverInterface $typeResolver, protected ?CrudlEntityManagerInterface $manager = null)
    {
        parent::__construct($em);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'class' => '',
            // 'rpp_valid_values' => [20],
            // 'rpp_default_value' => 20,
            'order_valid_fields' => null,
            'order_default_value' => null,
            'manager' => $this->manager,
            'filter_fields' => null,
        ]);

        $resolver->setRequired('manager');
        $resolver->addAllowedTypes('manager', CrudlEntityManagerInterface::class);

        $resolver->setNormalizer('class', function (Options $options, $value) {
            return $value ?: $options['manager']->getEntityClass();
        });

        $resolver->setNormalizer('order_valid_fields', function (Options $options, $value) {
            return $value ?: array_map(fn (ReflectionProperty $property): string => $property->getName(), $options['manager']->getEntityClassReflection()->getProperties());
        });

        $resolver->setNormalizer('order_default_value', function (Options $options, $value) {
            return $value ?: $options['order_valid_fields'][0];
        });
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        foreach ($this->getFilterFields($options) as $field => $fieldConfig) {
            $builder->add($field, $this->typeResolver->resolveTypeClass($fieldConfig['type'] ?? null), $fieldConfig['type_options'] ?? []);
        }
    }

    protected function getFilterFields(array $options): array
    {
        if (!is_null($options['filter_fields'])) {
            return $options['filter_fields'];
        }

        /** @var CrudlEntityManagerInterface $manager */
        $manager = $options['manager'];
        $filterFields = [];

        foreach ($manager->getEntityClassReflection()->getProperties() as $property) {
            $propertyPath = '['.$property->getName().']';

            /* @phpstan-ignore-next-line */
            if ('string' === $property->getType()->getName()) {
                // TODO SEARCH IN DOCTRINE MAPPING, FOR MORE OPTIONS
                $propertyPath = '['.$property->getName().'__like]';
            }

            $filterFields[$property->getName()] = [
                'type' => TextType::class,
                'type_options' => [
                    'property_path' => $propertyPath,
                ],
            ];
        }

        $filterFields['search'] = [
            'type' => SubmitType::class,
            'type_options' => [],
        ];

        return $filterFields;
    }
}
