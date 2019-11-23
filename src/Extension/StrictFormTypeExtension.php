<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Generator;
use InvalidArgumentException;
use Strictify\FormMapper\DataMapper\StrictFormMapper;
use Strictify\FormMapper\Exception\FactoryException;
use Strictify\FormMapper\Service\AccessorInterface;
use Strictify\FormMapper\Service\CallableReaderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;
use function iterator_to_array;
use function sprintf;

class StrictFormTypeExtension extends AbstractTypeExtension
{
    private $callableReader;
    private $accessor;
    private $translator;

    public function __construct(CallableReaderInterface $callableReader, AccessorInterface $accessor, ?TranslatorInterface $translator)
    {
        $this->callableReader = $callableReader;
        $this->accessor = $accessor;
        $this->translator = $translator;
    }

    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (true === $options['compound']) {
            $originalMapper = $builder->getDataMapper();
            if (!$originalMapper) {
                throw new InvalidArgumentException('Mapper not found');
            }

            $builder->setDataMapper(new StrictFormMapper($originalMapper, $this->accessor, $this->translator));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'get_value' => null,
            'update_value' => null,
            'add_value' => null,
            'remove_value' => null,
            'write_error_message' => 'Cannot write this type',
            'factory' => null,
            'factory_error_message' => 'Some fields are not valid, please correct them.',
        ]);
        $resolver->setAllowedTypes('get_value', ['null', 'callable']);
        $resolver->setAllowedTypes('update_value', ['null', 'callable']);
        $resolver->setAllowedTypes('add_value', ['null', 'callable']);
        $resolver->setAllowedTypes('remove_value', ['null', 'callable']);
        $resolver->setAllowedTypes('write_error_message', ['null', 'string']);
        $resolver->setAllowedTypes('factory', ['null', 'callable']);
        $resolver->setAllowedTypes('factory_error_message', ['null', 'string']);

        $resolver->setNormalizer('get_value', function (Options $options, ?callable $getter) {
            if ($options['add_value'] && !$options['remove_value']) {
                throw new InvalidOptionsException('You cannot use "add_value" without "remove_value".');
            }
            if ($options['remove_value'] && !$options['add_value']) {
                throw new InvalidOptionsException('You cannot use "remove_value" without "add_value".');
            }
            if ($options['update_value'] && $options['add_value']) {
                throw new InvalidOptionsException('You cannot use "update_value" when adder and remover is set.');
            }

            $isUpdaterSet = $options['update_value'] || $options['add_value'];
            if (!$getter && $isUpdaterSet) {
                throw new InvalidOptionsException('You must define "get_value".');
            }
            if ($getter && !$isUpdaterSet) {
                throw new InvalidOptionsException('You cannot use "get_value" without "update_value" or using "add_value" and "remove_value".');
            }

            return $getter;
        });

        $resolver->setNormalizer('empty_data', function (Options $options, $value) {
            /** @var callable|null $factory */
            $factory = $options['factory'];
            if (!$factory) {
                return $value;
            }
            $errorMessage = $options['factory_error_message'];

            return function (FormInterface $form) use ($factory, $errorMessage) {
                return $this->getDataFromCallable($form, $factory, $errorMessage);
            };
        });
    }

    /**
     * @return mixed
     */
    private function getDataFromCallable(FormInterface $form, callable $factory, ?string $errorMessage)
    {
        try {
            $arguments = $this->getArgumentsFromFactory($form, $factory);
            $arguments = iterator_to_array($arguments, false);

            return $factory(...$arguments);
        } catch (TypeError $e) {
            if ($errorMessage) {
                $translatedMessage = $this->translator ? $this->translator->trans($errorMessage) : $errorMessage;
                $form->addError(new FormError($translatedMessage, null, [], null, $e));
            }

            return null;
        }
    }

    private function getArgumentsFromFactory(FormInterface $form, callable $factory): Generator
    {
        $reader = $this->callableReader;
        $reflection = $reader->getReflection($factory);
        foreach ($reflection->getParameters() as $parameter) {
            $parameterName = $parameter->getName();
            if (!$parameter->hasType()) {
                throw new InvalidArgumentException(sprintf('No typehint for parameter name "%s".', $parameterName));
            }
            $type = $parameter->getClass();
            if ($type && $type->implementsInterface(FormInterface::class)) {
                yield $form;
            } else {
                if (!$form->has($parameterName)) {
                    throw new FactoryException(sprintf('No form field with name "%s".', $parameterName));
                }
                yield $form->get($parameterName)->getData();
            }
        }
    }
}
