<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Closure;
use Generator;
use ReflectionFunction;
use ReflectionParameter;
use Doctrine\Instantiator\Instantiator;
use Symfony\Component\Form\FormInterface;
use Strictify\FormMapper\VO\SubmittedData;
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
use function iterator_to_array;

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
            /** @var SubmittedData $store */

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

            return $this->createEmptyDataClosure($factory);
        });
    }

    private function createEmptyDataClosure(Closure $factory): Closure
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
                $arguments = iterator_to_array($this->getFactoryArguments($form, $factory), false);
                /** @psalm-var mixed $values */
                $data = $factory(...$arguments);

                return $data;
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
        // special name; this will retrieve data of parent class (although technically it is grandparent)
        if ($name === 'parent') {
            $parent = $form->getParent();
            if (!$parent || !$grandParent = $parent->getParent()) {
                throw new MissingFactoryFieldException('No parent form.');
            }
            /** @var Closure $emptyData */
            $emptyData = $grandParent->getConfig()->getOption('empty_data');
            /** @psalm-var mixed $caller */
            $parentData = $emptyData($grandParent);
            if (null === $parentData && !$parameter->allowsNull()) {
                throw new MissingFactoryFieldException('Parent cannot be null.');
            }

            return $parentData;
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
