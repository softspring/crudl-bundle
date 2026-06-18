<?php

declare(strict_types=1);

namespace Softspring\CrudlBundle\Tests\Unit\Form;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Softspring\Component\CrudlController\Manager\CrudlEntityManagerInterface;
use Softspring\Component\DynamicFormType\Form\Resolver\TypeResolverInterface;
use Softspring\CrudlBundle\Form\DefaultFilterForm;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DefaultFilterFormTest extends TestCase
{
    public function testConfiguresDefaultsFromManager(): void
    {
        $manager = $this->createMock(CrudlEntityManagerInterface::class);
        $manager->expects(self::any())->method('getEntityClass')->willReturn(FilterableEntity::class);
        $manager->expects(self::any())->method('getEntityClassReflection')->willReturn(new ReflectionClass(FilterableEntity::class));
        $resolver = new OptionsResolver();

        $this->createForm(manager: $manager)->configureOptions($resolver);

        $options = $resolver->resolve([
            'query_builder' => static fn (): null => null,
        ]);

        self::assertSame(FilterableEntity::class, $options['class']);
        self::assertSame(['title', 'published'], $options['order_valid_fields']);
        self::assertSame('title', $options['order_default_value']);
        self::assertNull($options['filter_fields']);
    }

    public function testBuildsConfiguredFilterFieldsAfterPaginatorFields(): void
    {
        $typeResolver = $this->createMock(TypeResolverInterface::class);
        $typeResolver->expects(self::any())->method('resolveTypeClass')->willReturnMap([
            ['text', TextType::class],
        ]);

        $addedFields = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::any())->method('add')->willReturnCallback(function (string $name, ?string $type = null, array $options = []) use (&$addedFields, $builder): FormBuilderInterface {
            $addedFields[$name] = $type;

            return $builder;
        });

        $this->createForm(typeResolver: $typeResolver)->buildForm($builder, [
            'page_field_name' => 'page',
            'rpp_field_name' => 'rpp',
            'rpp_valid_values' => ['50'],
            'order_field_name' => 'order',
            'order_valid_fields' => ['title'],
            'order_direction_field_name' => 'sort',
            'order_direction_valid_fields' => ['asc', 'desc'],
            'query_builder' => static fn (): null => null,
            'filter_fields' => [
                'title' => [
                    'type' => 'text',
                ],
            ],
        ]);

        self::assertSame(TextType::class, $addedFields['title']);
        self::assertArrayHasKey('rpp', $addedFields);
        self::assertArrayHasKey('order', $addedFields);
        self::assertArrayHasKey('sort', $addedFields);
    }

    public function testBuildsDefaultFilterFieldsFromEntityReflection(): void
    {
        $manager = $this->createMock(CrudlEntityManagerInterface::class);
        $manager->expects(self::any())->method('getEntityClassReflection')->willReturn(new ReflectionClass(FilterableEntity::class));
        $typeResolver = $this->createMock(TypeResolverInterface::class);
        $typeResolver->expects(self::any())->method('resolveTypeClass')->willReturnArgument(0);

        $addedFields = [];
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::any())->method('add')->willReturnCallback(function (string $name, ?string $type = null, array $options = []) use (&$addedFields, $builder): FormBuilderInterface {
            $addedFields[$name] = ['type' => $type, 'options' => $options];

            return $builder;
        });

        $this->createForm(typeResolver: $typeResolver)->buildForm($builder, [
            'page_field_name' => 'page',
            'rpp_field_name' => 'rpp',
            'rpp_valid_values' => ['50'],
            'order_field_name' => 'order',
            'order_valid_fields' => ['title'],
            'order_direction_field_name' => 'sort',
            'order_direction_valid_fields' => ['asc', 'desc'],
            'query_builder' => static fn (): null => null,
            'filter_fields' => null,
            'manager' => $manager,
        ]);

        self::assertSame(TextType::class, $addedFields['title']['type']);
        self::assertSame('[title__like]', $addedFields['title']['options']['property_path']);
        self::assertSame(TextType::class, $addedFields['published']['type']);
        self::assertSame('[published]', $addedFields['published']['options']['property_path']);
        self::assertSame(SubmitType::class, $addedFields['search']['type']);
    }

    private function createForm(?EntityManagerInterface $entityManager = null, ?TypeResolverInterface $typeResolver = null, ?CrudlEntityManagerInterface $manager = null): DefaultFilterForm
    {
        return new DefaultFilterForm(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $typeResolver ?? $this->createStub(TypeResolverInterface::class),
            $manager,
        );
    }
}

final class FilterableEntity
{
    public string $title = '';

    public bool $published = false;
}
