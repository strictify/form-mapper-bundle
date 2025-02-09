<?php

declare(strict_types=1);

namespace Strictify\FormMapper;

use Closure;

/**
 * @psalm-type O=array{
 *     get_value: ?Closure(): mixed,
 *     update_value: Closure,
 *     add_value: Closure,
 *     remove_value: Closure,
 *     factory: ?Closure,
 *     multiple: bool,
 *     entry_type?: string,
 *     compare: Closure,
 *     remove_entry: ?Closure,
 *     ...
 * }
 *
 * @psalm-type D=array<mixed>|object|null
 *
 * @see Closure
 */
class Types
{

}
