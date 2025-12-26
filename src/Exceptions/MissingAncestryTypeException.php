<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Exceptions;

/**
 * Exception thrown when an ancestry type is not set before an operation.
 *
 * This exception signals that an ancestry operation was attempted without
 * first specifying which hierarchy type to operate on, which is required
 * for all hierarchy management operations.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MissingAncestryTypeException extends InvalidConfigurationException
{
    /**
     * Create an exception for missing hierarchy type.
     *
     * @return self The exception instance with a message instructing the developer
     *              to call the type() method before proceeding with the operation
     */
    public static function forOperation(): self
    {
        return new self('Ancestor type must be set. Call type() first.');
    }
}
