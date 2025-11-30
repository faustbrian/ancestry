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
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class NodeMoved
{
    use Dispatchable;

    public function __construct(
        public Model $node,
        public ?Model $previousParent,
        public ?Model $newParent,
        public string $type,
    ) {}
}
