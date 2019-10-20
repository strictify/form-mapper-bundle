<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Application\Factory;

use Strictify\FormMapper\Extension\StrictFormTypeExtension;
use Strictify\FormMapper\Service\Accessor;
use Strictify\FormMapper\Service\AccessorInterface;
use Strictify\FormMapper\Service\CallableReader;
use Strictify\FormMapper\Service\CallableReaderInterface;
use Strictify\FormMapper\Service\Comparator\DateTimeComparator;
use Symfony\Contracts\Translation\TranslatorInterface;

class Factory
{
    public static function getAccessor(): AccessorInterface
    {
        $dateTimeComparator = new DateTimeComparator();

        return new Accessor([$dateTimeComparator]);
    }

    public static function getCallableReader(): CallableReaderInterface
    {
        return new CallableReader();
    }

    public static function getFormExtension(): StrictFormTypeExtension
    {
        $reader = self::getCallableReader();

        return new StrictFormTypeExtension($reader, self::getTranslator());
    }

    public static function getTranslator(): TranslatorInterface
    {
        return new class implements TranslatorInterface {
            public function trans($id, array $parameters = [], $domain = null, $locale = null): string
            {
                return $id;
            }
        };
    }
}
