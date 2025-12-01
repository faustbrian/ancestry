<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Exceptions;

use RuntimeException;

/**
 * Exception thrown for invalid configuration.
 *
 * This exception signals configuration errors in the ancestry package setup that would
 * prevent proper hierarchy management, such as conflicting settings or missing required
 * configuration values.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidConfigurationException extends RuntimeException
{
    /**
     * Create an exception for conflicting morph key maps.
     *
     * This factory method is called when both morphKeyMap and enforceMorphKeyMap
     * configuration options are set simultaneously, which creates an ambiguous
     * configuration state that must be resolved by choosing one approach.
     *
     * @return self The exception instance with a message explaining the configuration
     *              conflict between the two morph key mapping strategies
     */
    public static function conflictingMorphKeyMaps(): self
    {
        return new self(
            'Cannot configure both "morphKeyMap" and "enforceMorphKeyMap". Choose one or the other.',
        );
    }

    /**
     * Create an exception for missing hierarchy type.
     *
     * This factory method is called when an ancestry operation is attempted without
     * first specifying which hierarchy type to operate on, which is required for
     * all hierarchy management operations.
     *
     * @return self The exception instance with a message instructing the developer
     *              to call the type() method before proceeding with the operation
     */
    public static function missingAncestryType(): self
    {
        return new self('Ancestor type must be set. Call type() first.');
    }
}
