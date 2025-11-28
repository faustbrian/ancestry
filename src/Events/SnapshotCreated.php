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
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class SnapshotCreated
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @param Model                   $context   The model the snapshot is attached to
     * @param string                  $type      The hierarchy type
     * @param int                     $count     Number of snapshots created
     * @param array<AncestorSnapshot> $snapshots The created snapshots
     */
    public function __construct(
        public Model $context,
        public string $type,
        public int $count,
        public array $snapshots = [],
    ) {}
}
