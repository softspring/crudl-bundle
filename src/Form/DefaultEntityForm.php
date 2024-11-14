<?php

namespace Softspring\CrudlBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Softspring\Component\CrudlController\Manager\CrudlEntityManagerInterface;
use Softspring\Component\DynamicFormType\Form\Resolver\TypeResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultEntityForm extends AbstractType
{
    public function __construct(protected EntityManagerInterface $em, protected TypeResolverInterface $typeResolver, protected ?CrudlEntityManagerInterface $manager = null)
    {

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'manager' => $this->manager,
            'entity_fields' => null,
        ]);

        $resolver->setRequired('manager');
        $resolver->addAllowedTypes('manager', CrudlEntityManagerInterface::class);

        $resolver->setNormalizer('data_class', function ($options, $value) {
            return $value ?: $options['manager']->getEntityClass();
        });
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->getEntityFields($options) as $field => $fieldConfig) {
            $builder->add($field, $this->typeResolver->resolveTypeClass($fieldConfig['type'] ?? null), $fieldConfig['type_options'] ?? []);
        }
    }

    protected function getEntityFields(array $options): array
    {
        // TODO, GET FROM CONFIG
        if ($options['entity_fields'] ?? false) {
            return $options['entity_fields'];
        }

        /** @var CrudlEntityManagerInterface $manager */
        $manager = $options['manager'];
        $entityFields = [];

        $entityReflectionClass = $manager->getEntityClassReflection();
        $entityMetadata = $this->em->getClassMetadata($manager->getEntityClass());

        // add simple fields
        foreach ($entityMetadata->getFieldNames() as $fieldName) {
            $fieldMapping = $entityMetadata->getFieldMapping($fieldName);

            // skip not public fields without setter
            if (!$entityReflectionClass->getProperty($fieldName)->isPublic() && !$entityReflectionClass->hasMethod('set' . ucfirst($fieldName))) {
                continue;
            }

            switch ($fieldMapping->type) {
                case 'string':
                    $entityFields[$fieldName] = [
                        'type' => TextType::class,
                    ];
                    break;

                default:
                    $entityFields[$fieldName] = [
                        'type' => null, // default guess type
                    ];
                    break;
            }
        }

        // add associations
        foreach ($entityMetadata->getAssociationNames() as $associationName) {
            $associationMapping = $entityMetadata->getAssociationMapping($associationName);

            switch ($associationMapping['type']) {
                case 1: // one to one
                    // skip not public fields without setter
                    if (!$entityReflectionClass->getProperty($associationName)->isPublic() && !$entityReflectionClass->hasMethod('set' . ucfirst($associationName))) {
                        break;
                    }
                    $entityFields[$associationName] = [
                        'type' => null, // default guess type
                    ];
                    break;

                case 2: // many to one
                    $entityFields[$associationName] = [
                        'type' => null, // default guess type
                    ];
                    break;
            }
        }

//        $entityFields['save'] = [
//            'type' => SubmitType::class,
//            'type_options' => [],
//        ];

        return $entityFields;
    }
}
