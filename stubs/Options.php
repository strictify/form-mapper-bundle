<?php

declare(strict_types=1);


namespace Symfony\Component\OptionsResolver;

/**
 * @template T as array{get_value: \Closure, update_value: \Closure, add_value: \Closure, remove_value: \Closure, factory: \Closure}
 * @template-extends \ArrayAccess<T>
 */
interface Options extends \ArrayAccess, \Countable
{
}
