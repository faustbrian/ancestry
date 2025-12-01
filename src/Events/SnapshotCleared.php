<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when hierarchy snapshots are cleared.
 *
 * This event fires when previously stored hierarchy snapshots associated with a specific
 * model and hierarchy type are deleted, typically during cleanup or recalculation operations.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class SnapshotCleared
{
    use Dispatchable;

    /**
     * Create a new snapshot cleared event instance.
     *
     * @param Model  $context The model instance that the cleared snapshots were attached to,
     *                        serving as the context or owner of the snapshot data
     * @param string $type    The hierarchy type identifier that classifies which hierarchy
     *                        structure the snapshots belonged to (e.g., 'organizational', 'taxonomic')
     * @param int    $count   The total number of snapshot records that were removed from
     *                        the database during this clearing operation
     */
    public function __construct(
        public Model $context,
        public string $type,
        public int $count,
    ) {}
}
