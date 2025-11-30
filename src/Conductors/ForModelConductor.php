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
use Cline\Ancestry\Exceptions\InvalidConfigurationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function throw_if;

/**
 * Fluent conductor for hierarchy operations on a specific model.
 *
 * Provides a chainable API for managing a model's hierarchical relationships.
 *
 * ```php
 * Ancestry::for($user)
 *     ->type('seller')
 *     ->attachTo($parentSeller);
 *
 * Ancestry::for($seller)
 *     ->type('seller')
 *     ->ancestors();
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ForModelConductor
{
    private AncestryType|string|null $type = null;

    public function __construct(
        private readonly AncestryService $service,
        private readonly Model $model,
    ) {}

    /**
     * Set the hierarchy type for subsequent operations.
     */
    public function type(AncestryType|string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Add this model to a hierarchy (create self-reference).
     */
    public function add(?Model $parent = null): self
    {
        $this->service->addToAncestry($this->model, $this->getType(), $parent);

        return $this;
    }

    /**
     * Attach this model to a parent.
     */
    public function attachTo(Model $parent): self
    {
        $this->service->attachToParent($this->model, $parent, $this->getType());

        return $this;
    }

    /**
     * Detach this model from its parent (become root).
     */
    public function detach(): self
    {
        $this->service->detachFromParent($this->model, $this->getType());

        return $this;
    }

    /**
     * Remove this model from the hierarchy completely.
     */
    public function remove(): self
    {
        $this->service->removeFromAncestry($this->model, $this->getType());

        return $this;
    }

    /**
     * Move this model to a new parent.
     */
    public function moveTo(?Model $newParent): self
    {
        $this->service->moveToParent($this->model, $newParent, $this->getType());

        return $this;
    }

    /**
     * Get all ancestors.
     *
     * @return Collection<int, Model>
     */
    public function ancestors(bool $includeSelf = false, ?int $maxDepth = null): Collection
    {
        return $this->service->getAncestors($this->model, $this->getType(), $includeSelf, $maxDepth);
    }

    /**
     * Get all descendants.
     *
     * @return Collection<int, Model>
     */
    public function descendants(bool $includeSelf = false, ?int $maxDepth = null): Collection
    {
        return $this->service->getDescendants($this->model, $this->getType(), $includeSelf, $maxDepth);
    }

    /**
     * Get the direct parent.
     */
    public function parent(): ?Model
    {
        return $this->service->getDirectParent($this->model, $this->getType());
    }

    /**
     * Get the direct children.
     *
     * @return Collection<int, Model>
     */
    public function children(): Collection
    {
        return $this->service->getDirectChildren($this->model, $this->getType());
    }

    /**
     * Get siblings (models with the same parent).
     *
     * @return Collection<int, Model>
     */
    public function siblings(bool $includeSelf = false): Collection
    {
        return $this->service->getSiblings($this->model, $this->getType(), $includeSelf);
    }

    /**
     * Check if this model is an ancestor of another.
     */
    public function isAncestorOf(Model $model): bool
    {
        return $this->service->isAncestorOf($this->model, $model, $this->getType());
    }

    /**
     * Check if this model is a descendant of another.
     */
    public function isDescendantOf(Model $model): bool
    {
        return $this->service->isDescendantOf($this->model, $model, $this->getType());
    }

    /**
     * Get the depth in the hierarchy.
     */
    public function depth(): int
    {
        return $this->service->getDepth($this->model, $this->getType());
    }

    /**
     * Get the root(s) of this model's hierarchy.
     *
     * @return Collection<int, Model>
     */
    public function roots(): Collection
    {
        return $this->service->getRoots($this->model, $this->getType());
    }

    /**
     * Build a tree structure from this model's descendants.
     *
     * @return array{model: Model, children: array<int, mixed>}
     */
    public function tree(): array
    {
        return $this->service->buildTree($this->model, $this->getType());
    }

    /**
     * Get the path from root to this model.
     *
     * @return Collection<int, Model>
     */
    public function path(): Collection
    {
        return $this->service->getPath($this->model, $this->getType());
    }

    /**
     * Check if this model is in a hierarchy.
     */
    public function isInAncestry(): bool
    {
        return $this->service->isInAncestry($this->model, $this->getType());
    }

    /**
     * Check if this model is a root.
     */
    public function isRoot(): bool
    {
        return $this->service->isRoot($this->model, $this->getType());
    }

    /**
     * Check if this model is a leaf (no children).
     */
    public function isLeaf(): bool
    {
        return $this->service->isLeaf($this->model, $this->getType());
    }

    /**
     * Get the hierarchy type, throwing if not set.
     */
    private function getType(): AncestryType|string
    {
        throw_if($this->type === null, InvalidConfigurationException::missingAncestryType());

        return $this->type;
    }
}
