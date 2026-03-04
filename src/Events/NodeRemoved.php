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
 * Event dispatched when a node is completely removed from a hierarchy.
 *
 * This event fires when a node is detached from its parent and removed from a specific
 * hierarchy structure entirely, severing all parent-child relationships for that hierarchy type.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class NodeRemoved
{
    use Dispatchable;

    /**
     * Create a new node removed event instance.
     *
     * @param Model  $node The model instance that was removed from the hierarchy structure,
     *                     including all its ancestor and descendant relationships for this type
     * @param string $type The hierarchy type identifier that classifies which hierarchy
     *                     structure the node was removed from (e.g., 'organizational', 'taxonomic')
     */
    public function __construct(
        public Model $node,
        public string $type,
    ) {}
}
