<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Exceptions;

/**
 * Exception thrown when conflicting morph key maps are configured.
 *
 * This exception signals that both morphKeyMap and enforceMorphKeyMap
 * configuration options are set simultaneously, which creates an ambiguous
 * configuration state that must be resolved by choosing one approach.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ConflictingMorphKeyMapsException extends InvalidConfigurationException
{
    /**
     * Create an exception for conflicting morph key maps.
     *
     * @return self The exception instance with a message explaining the configuration
     *              conflict between the two morph key mapping strategies
     */
    public static function detected(): self
    {
        return new self(
            'Cannot configure both "morphKeyMap" and "enforceMorphKeyMap". Choose one or the other.',
        );
    }
}
