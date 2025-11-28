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
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidConfigurationException extends RuntimeException
{
    /**
     * Create an exception for conflicting morph key maps.
     */
    public static function conflictingMorphKeyMaps(): self
    {
        return new self(
            'Cannot configure both "morphKeyMap" and "enforceMorphKeyMap". Choose one or the other.',
        );
    }

    /**
     * Create an exception for missing hierarchy type.
     */
    public static function missingAncestryType(): self
    {
        return new self('Ancestor type must be set. Call type() first.');
    }
}
