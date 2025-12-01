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
 * This exception prevents the creation of invalid hierarchy structures where a node
 * would become its own ancestor, which would create an infinite loop in traversal
 * operations and violate the acyclic nature of tree structures.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class CircularReferenceException extends RuntimeException
{
    /**
     * Create an exception for a detected circular reference.
     *
     * This factory method constructs a descriptive error message identifying both
     * the child node attempting to be attached and the parent that would create
     * the circular reference.
     *
     * @param  Model $child  The model instance that is attempting to be attached as a child
     *                       node in the hierarchy relationship
     * @param  Model $parent The model instance that would become the parent, but doing so
     *                       would create a circular reference (the child is already an ancestor)
     * @return self  The exception instance with a formatted message detailing the circular
     *               reference attempt, including model types and primary keys for debugging
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
