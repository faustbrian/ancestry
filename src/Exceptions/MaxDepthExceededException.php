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
 * @author Brian Faust <brian@cline.sh>
 */
final class MaxDepthExceededException extends RuntimeException
{
    /**
     * Create an exception for exceeded depth.
     */
    public static function exceeded(int $maxDepth): self
    {
        return new self(sprintf(
            'Maximum hierarchy depth (%d levels) exceeded.',
            $maxDepth,
        ));
    }
}
