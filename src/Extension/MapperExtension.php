<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Closure;
use ReflectionFunction;
use Strictify\FormMapper\DataMapper\StrictFormMapper;
use Strictify\FormMapper\Service\Comparator;
use Strictify\FormMapper\Util\OptionsValidator;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use function array_map;
use function array_merge;
use function get_class;
use function in_array;

class MapperExtension extends AbstractTypeExtension
{
    private Comparator $comparator;

    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }

    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultMapper = $builder->getDataMapper();
        if (!$defaultMapper) {
            return;
        }
        $strictMapper = new StrictFormMapper($defaultMapper, $this->comparator);
        $builder->setDataMapper($strictMapper);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'get_value' => null,
            'update_value' => null,
            'add_value' => null,
            'remove_value' => null,
        ]);
        $resolver->setAllowedTypes('get_value', ['null', Closure::class]);
        $resolver->setAllowedTypes('update_value', ['null', Closure::class]);
        $resolver->setAllowedTypes('add_value', ['null', Closure::class]);
        $resolver->setAllowedTypes('remove_value', ['null', Closure::class]);

        $resolver->setNormalizer('get_value', fn (Options $options, ?Closure $getter) => $this->validateAccessors($options, $getter));
        $resolver->setNormalizer('constraints', fn (Options $options, array $constraints) => $this->normalizeConstraints($options, $constraints));
    }

    private function validateAccessors(Options $options, ?Closure $getter): ?Closure
    {
        if (!$getter) {
            return $getter;
        }
        $isCollection = isset($options['prototype']);

        OptionsValidator::validate($options['update_value'], $options['add_value'], $options['remove_value'], $isCollection);

        return $getter;
    }

    private function normalizeConstraints(Options $options, array $constraints): array
    {
        /** @var Closure|null $updater */
        $updater = $options['update_value'];
        if (!$updater) {
            return $constraints;
        }

        $reflection = new ReflectionFunction($updater);
        $params = $reflection->getParameters();
        if (0 === count($params)) {
            return $constraints;
        }

        $firstParam = $params[0];

        $type = $firstParam->getType();
        // first param is not typehinted, do not add extra constraints
        if (!$type) {
            return $constraints;
        }

        $extraConstraints = [];

        // existing constraints
        $constraintClasses = array_map(fn (Constraint $constraint) => get_class($constraint), $constraints);

        // add NotNull constraint, if not already defined and param cannot be nullable
        if (!$type->allowsNull() && !in_array(NotNull::class, $constraintClasses, true)) {
            $extraConstraints[] = new NotNull();
        }

        if (!in_array(Type::class, $constraintClasses, true)) {
            $extraConstraints[] = new Type(['type' => $type->getName()]);
        }

        // these extra constraints must be executed first
        return array_merge($extraConstraints, $constraints);
    }
}
