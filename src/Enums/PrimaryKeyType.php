<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Enums;

/**
 * Supported primary key types for Ancestry database tables.
 *
 * This enum defines the available primary key strategies:
 * - Id: Traditional auto-incrementing integers (default)
 * - Ulid: Universally Unique Lexicographically Sortable Identifiers
 * - Uuid: Universally Unique Identifiers
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum PrimaryKeyType: string
{
    /**
     * Auto-incrementing integer primary key.
     * Best for most applications with single-database deployments.
     */
    case Id = 'id';

    /**
     * ULID primary key.
     * Sortable, time-ordered identifiers ideal for distributed systems.
     */
    case Ulid = 'ulid';

    /**
     * UUID primary key.
     * Globally unique identifiers for maximum portability.
     */
    case Uuid = 'uuid';
}
