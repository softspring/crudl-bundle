<?php

declare(strict_types=1);

namespace Softspring\CrudlBundle\Tests\Unit\Form;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Softspring\Component\CrudlController\Manager\CrudlEntityManagerInterface;
use Softspring\Component\DynamicFormType\Form\Resolver\TypeResolverInterface;
use Softspring\CrudlBundle\Form\DefaultEntityForm;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DefaultEntityFormTest extends TestCase
{
    public function testConfiguresManagerAndDataClassDefaults(): void
    {
        $manager = $this->createMock(CrudlEntityManagerInterface::class);
        $manager->expects(self::any())->method('getEntityClass')->willReturn(EditableEntity::class);
        $resolver = new OptionsResolver();
        $resolver->setDefault('data_class', null);

        $this->createForm(manager: $manager)->configureOptions($resolver);

        $options = $resolver->resolve();

        self::assertSame($manager, $options['manager']);
        self::assertSame(EditableEntity::class, $options['data_class']);
        self::assertNull($options['entity_fields']);
    }

    public function testBuildsConfiguredEntityFields(): void
    {
        $typeResolver = $this->createMock(TypeResolverInterface::class);
        $typeResolver->expects(self::any())->method('resolveTypeClass')->willReturnMap([
            ['text', TextType::class],
            [null, null],
        ]);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::exactly(2))->method('add')->willReturnCallback(function (string $name, ?string $type = null, array $options = []) use ($builder): FormBuilderInterface {
            self::assertContains($name, ['title', 'enabled']);

            if ('title' === $name) {
                self::assertSame(TextType::class, $type);
                self::assertSame(['label' => 'Title'], $options);
            }

            if ('enabled' === $name) {
                self::assertNull($type);
                self::assertSame([], $options);
            }

            return $builder;
        });

        $this->createForm(typeResolver: $typeResolver)->buildForm($builder, [
            'entity_fields' => [
                'title' => [
                    'type' => 'text',
                    'type_options' => ['label' => 'Title'],
                ],
                'enabled' => [],
            ],
        ]);
    }

    public function testBuildsFieldsFromDoctrineMetadata(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::any())->method('getFieldNames')->willReturn(['title', 'internalCode']);
        $metadata->expects(self::any())->method('getFieldMapping')->willReturnCallback(static fn (string $field): object|array => self::createFieldMapping($field, 'title' === $field ? 'string' : 'integer'));
        $metadata->expects(self::any())->method('getAssociationNames')->willReturn(['category', 'owner']);
        $metadata->expects(self::any())->method('getAssociationMapping')->willReturnCallback(static fn (string $association): object|array => self::createAssociationMapping($association, 'category' === $association ? 2 : 1));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())->method('getClassMetadata')->with(EditableEntity::class)->willReturn($metadata);
        $manager = $this->createMock(CrudlEntityManagerInterface::class);
        $manager->expects(self::any())->method('getEntityClass')->willReturn(EditableEntity::class);
        $manager->expects(self::any())->method('getEntityClassReflection')->willReturn(new ReflectionClass(EditableEntity::class));
        $typeResolver = $this->createMock(TypeResolverInterface::class);
        $typeResolver->expects(self::any())->method('resolveTypeClass')->willReturnArgument(0);

        $addedFields = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::any())->method('add')->willReturnCallback(function (string $name, ?string $type = null, array $options = []) use (&$addedFields, $builder): FormBuilderInterface {
            $addedFields[$name] = $type;

            return $builder;
        });

        $this->createForm($entityManager, $typeResolver)->buildForm($builder, [
            'manager' => $manager,
        ]);

        self::assertSame([
            'title' => TextType::class,
            'category' => null,
            'owner' => null,
        ], $addedFields);
    }

    private function createForm(?EntityManagerInterface $entityManager = null, ?TypeResolverInterface $typeResolver = null, ?CrudlEntityManagerInterface $manager = null): DefaultEntityForm
    {
        return new DefaultEntityForm(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $typeResolver ?? $this->createStub(TypeResolverInterface::class),
            $manager,
        );
    }

    private static function createFieldMapping(string $field, string $type): object|array
    {
        if (class_exists('Doctrine\ORM\Mapping\FieldMapping')) {
            $fieldMappingClass = 'Doctrine\ORM\Mapping\FieldMapping';

            return new $fieldMappingClass($type, $field, $field);
        }

        return [
            'type' => $type,
            'fieldName' => $field,
            'columnName' => $field,
        ];
    }

    private static function createAssociationMapping(string $association, int $type): object|array
    {
        if (class_exists('Doctrine\ORM\Mapping\ManyToOneAssociationMapping')) {
            $mappingClass = 2 === $type ? 'Doctrine\ORM\Mapping\ManyToOneAssociationMapping' : 'Doctrine\ORM\Mapping\OneToOneOwningSideMapping';

            return $mappingClass::fromMappingArray([
                'fieldName' => $association,
                'sourceEntity' => EditableEntity::class,
                'targetEntity' => 2 === $type ? CategoryEntity::class : OwnerEntity::class,
                'type' => $type,
                'isOwningSide' => true,
            ]);
        }

        return [
            'fieldName' => $association,
            'sourceEntity' => EditableEntity::class,
            'targetEntity' => 2 === $type ? CategoryEntity::class : OwnerEntity::class,
            'type' => $type,
        ];
    }
}

final class EditableEntity
{
    public string $title = '';

    private int $internalCode = 0;

    private object $category;

    private object $owner;

    public function setOwner(object $owner): void
    {
        $this->owner = $owner;
    }

    public function setCategory(object $category): void
    {
        $this->category = $category;
    }

    public function getInternalCode(): int
    {
        return $this->internalCode;
    }

    public function getCategory(): object
    {
        return $this->category;
    }

    public function getOwner(): object
    {
        return $this->owner;
    }
}

final class CategoryEntity
{
}

final class OwnerEntity
{
}
