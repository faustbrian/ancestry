<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Events;

use Cline\Ancestry\Database\AncestorSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when a hierarchy snapshot is created.
 *
 * This event fires when new hierarchy snapshots are generated and persisted for a specific
 * model and hierarchy type, capturing the current state of ancestor-descendant relationships.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class SnapshotCreated
{
    use Dispatchable;

    /**
     * Create a new snapshot created event instance.
     *
     * @param Model                        $context   The model instance that the created snapshots are attached to,
     *                                                serving as the context or owner of the snapshot data
     * @param string                       $type      The hierarchy type identifier that classifies which hierarchy
     *                                                structure these snapshots represent (e.g., 'organizational', 'taxonomic')
     * @param int                          $count     The total number of snapshot records that were created and
     *                                                persisted to the database during this snapshot operation
     * @param array<int, AncestorSnapshot> $snapshots Array of the actual AncestorSnapshot model instances that were
     *                                                created, providing detailed information about each captured relationship
     */
    public function __construct(
        public Model $context,
        public string $type,
        public int $count,
        public array $snapshots = [],
    ) {}
}
