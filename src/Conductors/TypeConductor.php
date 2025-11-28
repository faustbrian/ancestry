<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Conductors;

use Cline\Ancestry\Contracts\AncestryService;
use Cline\Ancestry\Contracts\AncestryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Fluent conductor for hierarchy operations on a specific type.
 *
 * Provides a chainable API for working with all hierarchies of a specific type.
 *
 * ```php
 * Ancestry::ofType('seller')
 *     ->roots();
 *
 * Ancestry::ofType('seller')
 *     ->for($user)
 *     ->ancestors();
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class TypeConductor
{
    public function __construct(
        private AncestryService $service,
        private AncestryType|string $type,
    ) {}

    /**
     * Get a conductor for a specific model with this type pre-set.
     */
    public function for(Model $model): ForModelConductor
    {
        return new ForModelConductor($this->service, $model)->type($this->type);
    }

    /**
     * Get all root nodes for this hierarchy type.
     *
     * @return Collection<int, Model>
     */
    public function roots(): Collection
    {
        return $this->service->getRootNodes($this->type);
    }

    /**
     * Add a model to this hierarchy.
     */
    public function add(Model $model, ?Model $parent = null): ForModelConductor
    {
        $this->service->addToAncestry($model, $this->type, $parent);

        return $this->for($model);
    }
}
