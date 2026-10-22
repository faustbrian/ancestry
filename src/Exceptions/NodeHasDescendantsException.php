<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

use function implode;
use function sprintf;

/**
 * Exception thrown when a model that still has descendants is deleted.
 *
 * Deleting such a model would leave its descendants with paths to a node that
 * no longer exists, so the delete is blocked until the descendants are removed
 * or moved.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class NodeHasDescendantsException extends RuntimeException implements AncestryException
{
    /**
     * Create an exception for a delete blocked by descendants.
     *
     * @param  Model        $model The model whose delete was blocked
     * @param  list<string> $types The ancestry types in which the model has descendants
     * @return self         The exception instance naming the model and the blocking types
     */
    public static function cannotDelete(Model $model, array $types): self
    {
        /** @var null|int|string $key */
        $key = $model->getKey();

        return new self(sprintf(
            'Cannot delete [%s:%s] - it still has descendants in ancestry types [%s].',
            $model->getMorphClass(),
            (string) $key,
            implode(', ', $types),
        ));
    }
}
