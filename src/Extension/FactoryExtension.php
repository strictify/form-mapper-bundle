<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Closure;
use ReflectionFunction;
use ReflectionParameter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Strictify\FormMapper\Exception\FactoryExceptionInterface;
use Strictify\FormMapper\Exception\MissingFactoryFieldException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Strictify\FormMapper\Exception\InvalidFactorySignatureException;
use function is_a;
use function sprintf;
use function array_keys;
use function similar_text;

class FactoryExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'factory'            => null,
            'show_factory_error' => true,
        ]);

        $resolver->addAllowedTypes('factory', ['null', Closure::class]);
        $resolver->addAllowedTypes('show_factory_error', ['bool']);

        $resolver->setNormalizer('empty_data', function (Options $options, $default) {
            /** @var Closure|null $factory */
            $factory = $options['factory'];

            return $factory ? $this->createEmptyDataWrapper($factory) : $default;
        });
    }

    private function createEmptyDataWrapper(Closure $factory): Closure
    {
        return function (FormInterface $form) use ($factory) {
            try {
                $arguments = $this->getFactoryArguments($form, $factory);

                return $factory(...$arguments);
            } catch (InvalidFactorySignatureException $e) {
                throw new TransformationFailedException(invalidMessage: $e->getMessage());
            } catch (FactoryExceptionInterface) {
                return null;
            }
        };
    }

    private function getFactoryArguments(FormInterface $form, Closure $factory): array
    {
        $arguments = [];
        $reflection = new ReflectionFunction($factory);
        foreach ($reflection->getParameters() as $parameter) {
            $arguments[] = $this->getFormValue($form, $parameter);
        }

        return $arguments;
    }

    /**
     * Get form value based on parameter name.
     *
     * If typehint of parameter is FormInterface, then return form itself; name doesn't matter.
     */
    private function getFormValue(FormInterface $form, ReflectionParameter $parameter): mixed
    {
        $name = $parameter->getName();
        $type = $parameter->getType()?->getName();
        if ($type && is_a($type, FormInterface::class, true)) {
            return $form;
        }

        // Factory parameter is not submitted, or there is a typo; try to find best match and throw exception.
        if (!$form->has($name)) {
            return $parameter->isOptional() ? $parameter->getDefaultValue() : throw $this->createInvalidFactorySignatureException($form, $name);
        }

        /** @psalm-var mixed $value */
        $value = $form->get($name)->getData();
        $parameterType = $parameter->getType();

        // parameter is not typehinted, we don't care about what happens next, it is up to user and static analysis
        if (!$parameterType) {
            return $value;
        }

        // if submitted data is null but typehinted parameter doesn't allow it, throw exception
        if (null === $value && !$parameterType->allowsNull()) {
            throw new MissingFactoryFieldException(sprintf('Invalid type for field "%s".', $name));
        }

        return $value;
    }

    private function createInvalidFactorySignatureException(FormInterface $form, string $name): InvalidFactorySignatureException
    {
        $bestMatch = 0;
        $bestName = null;
        $all = array_keys($form->all());
        foreach ($all as $child) {
            similar_text((string)$child, $name, $percent);
            if ($percent > $bestMatch) {
                $bestName = $child;
                $bestMatch = $percent;
            }
        }
        $error = sprintf('Missing field "%s".', $name);
        if ($bestName) {
            $error .= sprintf(' Did you mean "%s"?', $bestName);
        }

        return new InvalidFactorySignatureException($error);
    }
}
