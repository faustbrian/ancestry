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
 * Event dispatched when a node is moved to a new parent in a hierarchy.
 *
 * This event fires whenever a node's parent changes within a specific hierarchy type,
 * providing access to both the previous and new parent for tracking relationship changes.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class NodeMoved
{
    use Dispatchable;

    /**
     * Create a new node moved event instance.
     *
     * @param Model      $node           The model instance that was moved to a new parent in the hierarchy
     * @param null|Model $previousParent The model instance that was the node's parent before the move,
     *                                   or null if the node was previously a root node without a parent
     * @param null|Model $newParent      The model instance that is now the node's parent after the move,
     *                                   or null if the node is being moved to become a root node
     * @param string     $type           The hierarchy type identifier that classifies which hierarchy
     *                                   structure this movement occurred in (e.g., 'organizational', 'taxonomic')
     */
    public function __construct(
        public Model $node,
        public ?Model $previousParent,
        public ?Model $newParent,
        public string $type,
    ) {}
}
