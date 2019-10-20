<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Generator;
use InvalidArgumentException;
use Strictify\FormMapper\DataMapper\StrictFormMapper;
use Strictify\FormMapper\Service\CallableReaderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Exception\OutOfBoundsException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;
use function iterator_to_array;
use function sprintf;

class StrictFormTypeExtension extends AbstractTypeExtension
{
    private $callableReader;
    private $translator;

    public function __construct(CallableReaderInterface $callableReader, ?TranslatorInterface $translator)
    {
        $this->callableReader = $callableReader;
        $this->translator = $translator;
    }

    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (true === $options['compound'] && null !== $options['get_value']) {
            $originalMapper = $builder->getDataMapper();
            if (!$originalMapper) {
                throw new InvalidArgumentException('Mapper not found');
            }

            $builder->setDataMapper(new StrictFormMapper($originalMapper));
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

        $resolver->setNormalizer('empty_data', function (Options $options, ?callable $value) {
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

    private function getDataFromCallable(FormInterface $form, callable $factory, ?string $errorMessage)
    {
        try {
            $arguments = $this->getArgumentsFromForm($form, $factory);
            $arguments = iterator_to_array($arguments, false);

            return $factory(...$arguments);
        } catch (OutOfBoundsException $e) {
            throw new OutOfBoundsException($e->getMessage().' Make sure your factory signature matches form fields.');
        } catch (TypeError $e) {
            if ($errorMessage) {
                $translatedMessage = $this->translator ? $this->translator->trans($errorMessage) : $errorMessage;
                $form->addError(new FormError($translatedMessage, null, [], null, $e));
            }

            return null;
        }
    }

    private function getArgumentsFromForm(FormInterface $form, callable $factory): Generator
    {
        $reader = $this->callableReader;
        $reflection = $reader->getReflection($factory);
        foreach ($reflection->getParameters() as $parameter) {
            if (!$parameter->hasType()) {
                throw new InvalidArgumentException(sprintf('No typehint for parameter name "%s".', $reflection->getName()));
            }
            $parameter->getClass();
            $type = $parameter->getClass();
            if ($type && $type->implementsInterface(FormInterface::class)) {
                yield $form;
            } else {
                yield $form->get($parameter->getName())->getData();
            }
        }
    }
}
