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
use Strictify\FormMapper\Exception\InvalidFactorySignatureException;
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

        $resolver->setNormalizer('empty_data', /** @param mixed $default */ function (Options $options, mixed $default) {
            /** @var Closure|null $factory */
            $factory = $options['factory'];

            return $factory ? $this->createEmptyDataWrapper($factory) : $default;
        });
    }

    private function createEmptyDataWrapper(Closure $factory): Closure
    {
        return function (FormInterface $form) use ($factory) {
            // we store the value of `empty_data` result. Multiple calls will return same result, thus preventing creation of multiple entities
            // it is important because of `parent`; look for this string under
            static $data = null;

            if ($data !== null) {
                return $data;
            }
            if (null !== $data = $form->getData()) {
                return $data;
            }
            try {
                $arguments = $this->getFactoryArguments($form, $factory);
                /** @psalm-var mixed $values */
                $data = $factory(...$arguments);

                return $data;
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
     * @return mixed
     */
    private function getFormValue(FormInterface $form, ReflectionParameter $parameter)
    {
        $name = $parameter->getName();
        $type = $parameter->getClass();
        if ($type && $type->implementsInterface(FormInterface::class)) {
            return $form;
        }

        // Factory parameter is not submitted, or there is a typo; try to find best match and throw exception.
        if (!$form->has($name)) {
            throw $this->createInvalidFactorySignatureException($form, $name);
        }

        /** @psalm-var mixed $value */
        $value = $form->get($name)->getData();

        $parameterType = $parameter->getType();

        // if submitted data is null but typehinted parameter doesn't allow it, throw exception
        if (null === $value && $parameterType && !$parameterType->allowsNull()) {
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
