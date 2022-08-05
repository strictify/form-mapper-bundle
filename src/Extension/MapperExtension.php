<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Closure;
use ReflectionFunction;
use ReflectionUnionType;
use ReflectionNamedType;
use Symfony\Component\Validator\Constraint;
use Strictify\FormMapper\Service\Comparator;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\NotNull;
use Strictify\FormMapper\DataMapper\StrictFormMapper;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use function in_array;
use function array_map;
use function get_class;
use function array_merge;

class MapperExtension extends AbstractTypeExtension
{
    public function __construct(private Comparator $comparator)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultMapper = $builder->getDataMapper();
        $strictMapper = new StrictFormMapper($defaultMapper, $this->comparator);
        $builder->setDataMapper($strictMapper);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'get_value'    => null,
            'update_value' => fn(mixed $data) => throw new MissingOptionsException('You have to create "update_value" callback.'),
            'add_value'    => fn() => null,
            'remove_value' => fn() => null,
            'compare'      => fn(mixed $defaultValue, mixed $submittedValue) => $defaultValue === $submittedValue,
        ]);
        $resolver->setAllowedTypes('get_value', ['null', Closure::class]);
        $resolver->setAllowedTypes('update_value', [Closure::class]);
        $resolver->setAllowedTypes('add_value', [Closure::class, 'null']);
        $resolver->setAllowedTypes('remove_value', [Closure::class, 'null']);
        $resolver->setAllowedTypes('compare', ['callable']);

        $resolver->setNormalizer('constraints', fn(Options $options, array $constraints) => $this->normalizeConstraints($options, $constraints));
    }

    /**
     * Reflect ``update_value`` and add NotNull/Type constraint if first parameter is typehinted and doesn't allow null value.
     *
     * So if user created callback like ``update_value => fn(string $name)``, this must have NotNull constraint.
     *
     * Otherwise, no validation will be displayed. Class-level annotation constraints don't apply because it could still be null (like factory failure).
     */
    private function normalizeConstraints(Options $options, array $constraints): array
    {
        /** @var Closure $updater */
        $updater = $options['update_value'];

        $reflection = new ReflectionFunction($updater);
        $params = $reflection->getParameters();
        if (!$firstParam = $params[0] ?? null) {
            return $constraints;
        }
        // first param is not typehinted, do not add extra constraints
        if (!$reflectionType = $firstParam->getType()) {
            return $constraints;
        }

        $extraConstraints = [];

        // existing constraints
        $constraintClasses = array_map(static fn(Constraint $constraint) => get_class($constraint), $constraints);

        // add NotNull constraint, if not already defined and param is not nullable
        if (!$reflectionType->allowsNull() && !in_array(NotNull::class, $constraintClasses, true)) {
            $extraConstraints[] = new NotNull();
        }

        if (!in_array(Type::class, $constraintClasses, true)) {
            $typeName = match (true) {
                $reflectionType instanceof ReflectionUnionType => array_map(static fn(ReflectionNamedType $reflectionNamedType) => $reflectionNamedType->getName(), $reflectionType->getTypes()),
                $reflectionType instanceof ReflectionNamedType => $reflectionType->getName(),
                default => null,
            };
            // we don't want validation of `mixed` type; static analysis will take care of it
            if ($typeName && $typeName !== 'mixed') {
                $extraConstraints[] = new Type(['type' => $typeName]);
            }
        }

        // these extra constraints must be executed first
        return array_merge($extraConstraints, $constraints);
    }
}
