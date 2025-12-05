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
 * Event dispatched when a node is attached to a parent in a hierarchy.
 *
 * This event fires after a node has been successfully attached to a parent,
 * allowing listeners to react to hierarchy changes (e.g., updating denormalized
 * data, triggering notifications, or invalidating caches).
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @property Model  $node   The model that was attached as a child
 * @property Model  $parent The model that received the new child
 * @property string $type   The hierarchy type identifier (e.g., 'seller', 'organization')
 *
 * @psalm-immutable
 */
final readonly class NodeAttached
{
    use Dispatchable;

    /**
     * Create a new NodeAttached event instance.
     *
     * @param Model  $node   The child model that was attached to the parent
     * @param Model  $parent The parent model that the node was attached to
     * @param string $type   The hierarchy type identifier for this relationship
     */
    public function __construct(
        public Model $node,
        public Model $parent,
        public string $type,
    ) {}
}
