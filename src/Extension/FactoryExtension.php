<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Closure;
use Generator;
use ReflectionFunction;
use ReflectionParameter;
use Strictify\FormMapper\Exception\FactoryExceptionInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Strictify\FormMapper\Exception\MissingFactoryFieldException;
use Strictify\FormMapper\Exception\InvalidFactorySignatureException;
use function array_keys;
use function similar_text;
use function iterator_to_array;
use function sprintf;
use function trigger_error;

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

        $resolver->setNormalizer('empty_data', /** @param mixed $default */ function (Options $options, $default) {
            /** @var Closure|null $factory */
            $factory = $options['factory'];
            if (!$factory) {
                return $default;
            }
            /** @var bool $showFactoryError */
            $showFactoryError = $options['show_factory_error'];

            return $this->createEmptyDataClosure($factory, $showFactoryError);
        });
    }

    private function createEmptyDataClosure(Closure $factory, bool $showFactoryError): Closure
    {
        return function (FormInterface $form) use ($factory, $showFactoryError) {
            try {
                $arguments = iterator_to_array($this->getFactoryArguments($form, $factory), false);

                return $factory(...$arguments);
            } catch (FactoryExceptionInterface $e) {
                if ($showFactoryError) {
                    $form->addError(new FormError($e->getMessage(), null, [], null, $e));
                }

                return null;
            }
        };
    }

    private function getFactoryArguments(FormInterface $form, Closure $factory): Generator
    {
        $reflection = new ReflectionFunction($factory);
        foreach ($reflection->getParameters() as $parameter) {
            yield $this->getFormValue($form, $parameter);
        }
    }

    /**
     * @return mixed|FormInterface
     */
    private function getFormValue(FormInterface $form, ReflectionParameter $parameter)
    {
        $name = $parameter->getName();
        $type = $parameter->getClass();
        if ($type && $type->implementsInterface(FormInterface::class)) {
            return $form;
        }

        if (!$form->has($name)) {
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
                $error .= sprintf('Did you mean "%s"?', $bestName);
            }
            throw new InvalidFactorySignatureException($error);
        }

        /** @psalm-var mixed $value */
        $value = $form->get($name)->getData();

        // if factory param is not typehinted, warn user about it.
        $parameterType = $parameter->getType();
        if (!$parameterType) {
            @trigger_error(sprintf('Factory parameter "%s" should be typehinted.', $name), E_USER_WARNING);
        }

        if (null === $value && $parameterType && !$parameterType->allowsNull()) {
            throw new MissingFactoryFieldException(sprintf('Invalid type for field "%s".', $name));
        }

        return $value;
    }
}
