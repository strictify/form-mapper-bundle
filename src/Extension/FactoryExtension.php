<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Closure;
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

        $resolver->setNormalizer('empty_data', function (Options $options, ?callable $default) {
            /** @var Closure|null $factory */
            $factory = $options['factory'];
            if (!$factory) {
                return $default;
            }

            return $this->createEmptyDataClosure($factory);
        });
    }

    private function createEmptyDataClosure(Closure $factory): Closure
    {
        return function (FormInterface $form) use ($factory) {
            try {
                $arguments = $this->getFormValues($form, $factory);

                return $factory(...$arguments);
            } catch (FactoryExceptionInterface $e) {
                $form->addError(new FormError($e->getMessage(), null, [], null, $e));

                return fn () => null;
            }
        };
    }

    private function getFormValues(FormInterface $form, Closure $factory): array
    {
        $reflection = new ReflectionFunction($factory);
        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            $arguments[] = $this->getFormValue($form, $parameter);
        }

        return $arguments;
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
        if (!$parameter->hasType()) {
            @trigger_error(sprintf('Factory parameter "%s" should be typehinted.', $name), E_USER_WARNING);

            return $value;
        }

        return $value;
    }
}
