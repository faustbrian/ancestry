<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry;

use Cline\Ancestry\Conductors\ForModelConductor;
use Cline\Ancestry\Conductors\TypeConductor;
use Cline\Ancestry\Contracts\AncestryService;
use Cline\Ancestry\Contracts\AncestryType;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Main entry point for the Ancestry hierarchy management system.
 *
 * Provides a fluent API for managing hierarchical relationships using
 * the closure table pattern. Use the facade or resolve from the container.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
#[Singleton()]
final readonly class AncestryManager
{
    public function __construct(
        private AncestryService $service,
    ) {}

    /**
     * Start a fluent interaction for a specific model.
     *
     * ```php
     * Ancestry::for($user)
     *     ->type('seller')
     *     ->attachTo($parentSeller);
     * ```
     */
    public function for(Model $model): ForModelConductor
    {
        return new ForModelConductor($this->service, $model);
    }

    /**
     * Start a fluent interaction for a specific hierarchy type.
     *
     * ```php
     * Ancestry::ofType('seller')
     *     ->roots();
     *
     * Ancestry::ofType('seller')
     *     ->for($user)
     *     ->ancestors();
     * ```
     */
    public function ofType(AncestryType|string $type): TypeConductor
    {
        return new TypeConductor($this->service, $type);
    }

    /**
     * Add a model to a hierarchy under an optional parent.
     */
    public function addToAncestry(
        Model $model,
        AncestryType|string $type,
        ?Model $parent = null,
    ): void {
        $this->service->addToAncestry($model, $type, $parent);
    }

    /**
     * Attach a model to a parent in an existing hierarchy.
     */
    public function attachToParent(
        Model $model,
        Model $parent,
        AncestryType|string $type,
    ): void {
        $this->service->attachToParent($model, $parent, $type);
    }

    /**
     * Detach a model from its parent (keeps in hierarchy as root).
     */
    public function detachFromParent(
        Model $model,
        AncestryType|string $type,
    ): void {
        $this->service->detachFromParent($model, $type);
    }

    /**
     * Remove a model completely from a hierarchy.
     */
    public function removeFromAncestry(
        Model $model,
        AncestryType|string $type,
    ): void {
        $this->service->removeFromAncestry($model, $type);
    }

    /**
     * Move a model to a new parent.
     */
    public function moveToParent(
        Model $model,
        ?Model $newParent,
        AncestryType|string $type,
    ): void {
        $this->service->moveToParent($model, $newParent, $type);
    }

    /**
     * Get all ancestors of a model.
     *
     * @return Collection<int, Model>
     */
    public function getAncestors(
        Model $model,
        AncestryType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection {
        return $this->service->getAncestors($model, $type, $includeSelf, $maxDepth);
    }

    /**
     * Get all descendants of a model.
     *
     * @return Collection<int, Model>
     */
    public function getDescendants(
        Model $model,
        AncestryType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection {
        return $this->service->getDescendants($model, $type, $includeSelf, $maxDepth);
    }

    /**
     * Get the direct parent of a model.
     */
    public function getDirectParent(
        Model $model,
        AncestryType|string $type,
    ): ?Model {
        return $this->service->getDirectParent($model, $type);
    }

    /**
     * Get the direct children of a model.
     *
     * @return Collection<int, Model>
     */
    public function getDirectChildren(
        Model $model,
        AncestryType|string $type,
    ): Collection {
        return $this->service->getDirectChildren($model, $type);
    }

    /**
     * Check if a model is an ancestor of another.
     */
    public function isAncestorOf(
        Model $potentialAncestor,
        Model $potentialDescendant,
        AncestryType|string $type,
    ): bool {
        return $this->service->isAncestorOf($potentialAncestor, $potentialDescendant, $type);
    }

    /**
     * Check if a model is a descendant of another.
     */
    public function isDescendantOf(
        Model $potentialDescendant,
        Model $potentialAncestor,
        AncestryType|string $type,
    ): bool {
        return $this->service->isDescendantOf($potentialDescendant, $potentialAncestor, $type);
    }

    /**
     * Get the depth of a model in the hierarchy.
     */
    public function getDepth(
        Model $model,
        AncestryType|string $type,
    ): int {
        return $this->service->getDepth($model, $type);
    }

    /**
     * Get the root ancestor(s) of a model.
     *
     * @return Collection<int, Model>
     */
    public function getRoots(
        Model $model,
        AncestryType|string $type,
    ): Collection {
        return $this->service->getRoots($model, $type);
    }

    /**
     * Build a tree structure from a model's descendants.
     *
     * @return array{model: Model, children: array<int, mixed>}
     */
    public function buildTree(
        Model $model,
        AncestryType|string $type,
    ): array {
        return $this->service->buildTree($model, $type);
    }

    /**
     * Get the full path from root to model.
     *
     * @return Collection<int, Model>
     */
    public function getPath(
        Model $model,
        AncestryType|string $type,
    ): Collection {
        return $this->service->getPath($model, $type);
    }

    /**
     * Check if a model is in a hierarchy.
     */
    public function isInAncestry(
        Model $model,
        AncestryType|string $type,
    ): bool {
        return $this->service->isInAncestry($model, $type);
    }

    /**
     * Check if a model is a root in a hierarchy.
     */
    public function isRoot(
        Model $model,
        AncestryType|string $type,
    ): bool {
        return $this->service->isRoot($model, $type);
    }

    /**
     * Check if a model is a leaf (has no children) in a hierarchy.
     */
    public function isLeaf(
        Model $model,
        AncestryType|string $type,
    ): bool {
        return $this->service->isLeaf($model, $type);
    }

    /**
     * Get siblings (models with the same parent) in a hierarchy.
     *
     * @return Collection<int, Model>
     */
    public function getSiblings(
        Model $model,
        AncestryType|string $type,
        bool $includeSelf = false,
    ): Collection {
        return $this->service->getSiblings($model, $type, $includeSelf);
    }

    /**
     * Get all root nodes for a hierarchy type.
     *
     * @return Collection<int, Model>
     */
    public function getRootNodes(AncestryType|string $type): Collection
    {
        return $this->service->getRootNodes($type);
    }
}
