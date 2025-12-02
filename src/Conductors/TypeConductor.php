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
use Cline\Ancestry\Exceptions\CircularReferenceException;
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
    /**
     * Create a new type-specific conductor instance.
     *
     * @param AncestryService     $service The ancestry service for hierarchy operations
     * @param AncestryType|string $type    The hierarchy type to work with
     */
    public function __construct(
        private AncestryService $service,
        private AncestryType|string $type,
    ) {}

    /**
     * Get a conductor for a specific model with this type pre-set.
     *
     * Returns a ForModelConductor instance with the hierarchy type already
     * configured for convenient chaining.
     *
     * @param  Model             $model The model to perform operations on
     * @return ForModelConductor Model conductor with type pre-configured
     */
    public function for(Model $model): ForModelConductor
    {
        return new ForModelConductor($this->service, $model)->type($this->type);
    }

    /**
     * Get all root nodes for this hierarchy type.
     *
     * Returns all models that have no parent in this specific hierarchy type.
     *
     * @return Collection<int, Model> Collection of root nodes
     */
    public function roots(): Collection
    {
        return $this->service->getRootNodes($this->type);
    }

    /**
     * Add a model to this hierarchy.
     *
     * Convenience method to add a model and return a conductor for further
     * operations on that model.
     *
     * @param Model      $model  The model to add to the hierarchy
     * @param null|Model $parent Optional parent model to attach under
     *
     * @throws CircularReferenceException If parent relationship would create a cycle
     *
     * @return ForModelConductor Model conductor for the added model
     */
    public function add(Model $model, ?Model $parent = null): ForModelConductor
    {
        $this->service->addToAncestry($model, $this->type, $parent);

        return $this->for($model);
    }
}
