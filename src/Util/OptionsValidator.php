<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Util;

use Closure;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @internal
 * @psalm-internal Strictify\FormMapper
 */
class OptionsValidator
{
    public static function validate(?Closure $updater, ?Closure $adder, ?Closure $remover, bool $isCollection): void
    {
        if ($isCollection) {
            if (!$adder) {
                throw new InvalidOptionsException('Missing "add_value".');
            }
            if (!$remover) {
                throw new InvalidOptionsException('Missing "remove_value".');
            }

            return;
        }

        if (!$updater) {
            throw new InvalidOptionsException('Missing "update_value".');
        }
    }
}
