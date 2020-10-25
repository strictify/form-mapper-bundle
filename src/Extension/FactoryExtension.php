<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Closure;
use Generator;
use ReflectionFunction;
use ReflectionParameter;
use Doctrine\Instantiator\Instantiator;
use Strictify\FormMapper\VO\SubmittedData;
use Strictify\FormMapper\Exception\FactoryExceptionInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Strictify\FormMapper\Exception\MissingFactoryFieldException;
use Strictify\FormMapper\Exception\InvalidFactorySignatureException;
use function array_keys;
use function similar_text;
use function iterator_to_array;
use function sprintf;

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
            '_stored_data' => null,
        ]);

        $resolver->addAllowedTypes('factory', ['null', Closure::class]);
        $resolver->addAllowedTypes('show_factory_error', ['bool']);

        $resolver->setNormalizer('empty_data', /** @param mixed $default */ function (Options $options, $default) {
            /** @var Closure|null $factory */
            $factory = $options['factory'];
            /** @var SubmittedData $store */
            $store = $options['_stored_data'];

            // if user hasn't defined "factory", improve existing empty_data with Instantiator component; we need to skip constructor
            if (!$factory) {
                /** @var string|null $class */
                $class = $options['data_class'];
                if (null !== $class) {
                    return function (FormInterface $form) use ($class) {
                        return $form->isEmpty() && !$form->isRequired() ? null : (new Instantiator())->instantiate($class);
                    };
                }

                return $default;
            }

            return $this->createEmptyDataClosure($factory, $store);
        });

        $resolver->setNormalizer('_stored_data', function (Options $options) {
            return new SubmittedData();
        });
    }

    private function createEmptyDataClosure(Closure $factory, SubmittedData $submittedData): Closure
    {
        return function (FormInterface $form) use ($factory, $submittedData) {
            if (null !== $form->getData()) {
                return $form->getData();
            }
            if ($submittedData->isPopulated()) {
                return $submittedData->getStore();
            }
            try {
                $arguments = iterator_to_array($this->getFactoryArguments($form, $factory), false);

                /** @psalm-var mixed $values */
                $values = $factory(...$arguments);
                $submittedData->setStore($values);

                return $values;
            } catch (FactoryExceptionInterface $e) {
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
        if ($name === 'caller') {
            $parent = $form->getParent();
            if (!$parent) {
                throw new MissingFactoryFieldException('No parent form.');
            }
            $grandParent = $parent->getParent();
            if (!$grandParent) {
                throw new MissingFactoryFieldException('No grandparent form.');
            }
            /** @var Closure $emptyData */
            $emptyData = $grandParent->getConfig()->getOption('empty_data');
            /** @psalm-var mixed $caller */
            $caller = $emptyData($grandParent);
            if (null === $caller && !$parameter->allowsNull()) {
                throw new MissingFactoryFieldException('Called cannot be null.');
            }

            return $caller;
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

        $parameterType = $parameter->getType();

        // if submitted data is null but typehinted parameter doesn't allow it, throw exception
        if (null === $value && $parameterType && !$parameterType->allowsNull()) {
            throw new MissingFactoryFieldException(sprintf('Invalid type for field "%s".', $name));
        }

        return $value;
    }
}
