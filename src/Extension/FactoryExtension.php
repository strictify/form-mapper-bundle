<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Closure;
use Generator;
use ReflectionFunction;
use ReflectionParameter;
use Strictify\FormMapper\Exception\FactoryExceptionInterface;
use Strictify\FormMapper\Exception\MissingFactoryFieldException;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function gettype;
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
        $resolver->setDefault('factory', null);
        $resolver->addAllowedTypes('factory', ['null', Closure::class]);

        $resolver->setDefault('show_factory_error', true);
        $resolver->addAllowedTypes('show_factory_error', ['bool']);

        $resolver->setNormalizer('empty_data', function (Options $options, ?callable $default) {
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

                return fn () => null;
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
        $type = $parameter->getClass();
        if ($type && $type->implementsInterface(FormInterface::class)) {
            return $form;
        }

        $name = $parameter->getName();
        if (!$form->has($name)) {
            throw new MissingFactoryFieldException(sprintf('Missing field "%s".', $name), $name);
        }

        $value = $form->get($name)->getData();

        // if factory param is not typehinted, early exit. It is up to user to take care of it.
        $parameterType = $parameter->getType();
        if (!$parameterType) {
            @trigger_error(sprintf('Factory parameter "%s" should be typehinted.', $name), E_USER_WARNING);

            return $value;
        }

        if (gettype($value) !== $parameterType->getName()) {
            throw new MissingFactoryFieldException(sprintf('Invalid type for field "%s".', $name), $name);
        }

        return $value;
    }
}
