<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Enums;

/**
 * Supported polymorphic relationship column types.
 *
 * This enum defines the available morphs strategies for polymorphic
 * relationships in the hierarchies table:
 * - Morph: Standard morphs with string type and integer/string ID
 * - UuidMorph: UUID-based morphs for UUID primary keys
 * - UlidMorph: ULID-based morphs for ULID primary keys
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum MorphType: string
{
    /**
     * Standard polymorphic relationship columns.
     * Uses string type column and appropriate ID column for the model.
     */
    case Morph = 'morph';

    /**
     * UUID-based polymorphic relationship columns.
     * Uses string type column and UUID ID column.
     */
    case UuidMorph = 'uuidMorph';

    /**
     * ULID-based polymorphic relationship columns.
     * Uses string type column and ULID ID column.
     */
    case UlidMorph = 'ulidMorph';
}
