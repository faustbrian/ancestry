<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Exceptions;

use Throwable;

/**
 * Marker interface for all Ancestry package exceptions.
 *
 * Consumers can catch this interface to handle any exception thrown by the
 * Ancestry package in a single catch block while still allowing granular
 * exception handling when needed.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface AncestryException extends Throwable
{
    // Marker interface - no methods required
}
