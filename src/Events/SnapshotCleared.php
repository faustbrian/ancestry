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
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class SnapshotCleared
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @param Model  $context The model the snapshots were attached to
     * @param string $type    The hierarchy type
     * @param int    $count   Number of snapshots that were cleared
     */
    public function __construct(
        public Model $context,
        public string $type,
        public int $count,
    ) {}
}
