<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a circular reference is detected in a hierarchy.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class CircularReferenceException extends RuntimeException
{
    /**
     * Create an exception for a detected circular reference.
     */
    public static function detected(Model $child, Model $parent): self
    {
        /** @var null|int|string $childKey */
        $childKey = $child->getKey();

        /** @var null|int|string $parentKey */
        $parentKey = $parent->getKey();

        return new self(sprintf(
            'Cannot attach [%s:%s] to [%s:%s] - this would create a circular reference.',
            $child->getMorphClass(),
            (string) $childKey,
            $parent->getMorphClass(),
            (string) $parentKey,
        ));
    }
}
