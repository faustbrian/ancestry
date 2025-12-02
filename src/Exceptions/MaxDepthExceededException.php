<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when the maximum hierarchy depth is exceeded.
 *
 * This exception prevents the creation of hierarchy structures that exceed the configured
 * maximum depth limit, which helps avoid performance issues and potential infinite recursion
 * in deeply nested hierarchical relationships.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MaxDepthExceededException extends RuntimeException implements AncestryException
{
    /**
     * Create an exception for exceeded depth.
     *
     * This factory method constructs an error message indicating the maximum allowed
     * depth that was exceeded during a hierarchy operation such as adding a new node
     * or moving an existing node to a deeper level.
     *
     * @param  int  $maxDepth The maximum allowed depth (number of levels) configured for
     *                        the hierarchy, which was exceeded by the attempted operation
     * @return self The exception instance with a formatted message indicating the
     *              depth limit that was violated
     */
    public static function exceeded(int $maxDepth): self
    {
        return new self(sprintf(
            'Maximum hierarchy depth (%d levels) exceeded.',
            $maxDepth,
        ));
    }
}
