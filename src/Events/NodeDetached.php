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
 * Event dispatched when a node is detached from its parent in a hierarchy.
 *
 * This event fires after a node has been successfully detached from its parent,
 * allowing listeners to react to hierarchy changes (e.g., cleanup operations,
 * cascading deletions, or audit logging).
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @property Model  $node           The model that was detached from its parent
 * @property Model  $previousParent The former parent model before detachment
 * @property string $type           The hierarchy type identifier (e.g., 'seller', 'organization')
 *
 * @psalm-immutable
 */
final readonly class NodeDetached
{
    use Dispatchable;

    /**
     * Create a new NodeDetached event instance.
     *
     * @param Model  $node           The child model that was detached
     * @param Model  $previousParent The parent model that the node was previously attached to
     * @param string $type           The hierarchy type identifier for this relationship
     */
    public function __construct(
        public Model $node,
        public Model $previousParent,
        public string $type,
    ) {}
}
