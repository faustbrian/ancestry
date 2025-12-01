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
use Cline\Ancestry\Exceptions\InvalidConfigurationException;
use Cline\Ancestry\Exceptions\MaxDepthExceededException;
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

    /**
     * Create a new model-specific conductor instance.
     *
     * @param AncestryService $service The ancestry service for hierarchy operations
     * @param Model           $model   The model to perform operations on
     */
    public function __construct(
        private readonly AncestryService $service,
        private readonly Model $model,
    ) {}

    /**
     * Set the hierarchy type for subsequent operations.
     *
     * @param  AncestryType|string $type The hierarchy type to use
     * @return self                For method chaining
     */
    public function type(AncestryType|string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Add this model to a hierarchy (create self-reference).
     *
     * @param null|Model $parent Optional parent to attach under
     *
     * @throws CircularReferenceException    If parent relationship would create a cycle
     * @throws InvalidConfigurationException If type not set
     *
     * @return self For method chaining
     */
    public function add(?Model $parent = null): self
    {
        $this->service->addToAncestry($this->model, $this->getType(), $parent);

        return $this;
    }

    /**
     * Attach this model to a parent.
     *
     * @param Model $parent The parent model to attach to
     *
     * @throws CircularReferenceException    If attaching would create a circular reference
     * @throws InvalidConfigurationException If type not set
     * @throws MaxDepthExceededException     If max depth would be exceeded
     *
     * @return self For method chaining
     */
    public function attachTo(Model $parent): self
    {
        $this->service->attachToParent($this->model, $parent, $this->getType());

        return $this;
    }

    /**
     * Detach this model from its parent (become root).
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return self For method chaining
     */
    public function detach(): self
    {
        $this->service->detachFromParent($this->model, $this->getType());

        return $this;
    }

    /**
     * Remove this model from the hierarchy completely.
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return self For method chaining
     */
    public function remove(): self
    {
        $this->service->removeFromAncestry($this->model, $this->getType());

        return $this;
    }

    /**
     * Move this model to a new parent.
     *
     * @param null|Model $newParent The new parent model, or null for root
     *
     * @throws CircularReferenceException    If move would create a circular reference
     * @throws InvalidConfigurationException If type not set
     *
     * @return self For method chaining
     */
    public function moveTo(?Model $newParent): self
    {
        $this->service->moveToParent($this->model, $newParent, $this->getType());

        return $this;
    }

    /**
     * Get all ancestors.
     *
     * @param bool     $includeSelf Whether to include the model itself
     * @param null|int $maxDepth    Maximum depth to traverse
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return Collection<int, Model> Collection of ancestor models
     */
    public function ancestors(bool $includeSelf = false, ?int $maxDepth = null): Collection
    {
        return $this->service->getAncestors($this->model, $this->getType(), $includeSelf, $maxDepth);
    }

    /**
     * Get all descendants.
     *
     * @param bool     $includeSelf Whether to include the model itself
     * @param null|int $maxDepth    Maximum depth to traverse
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return Collection<int, Model> Collection of descendant models
     */
    public function descendants(bool $includeSelf = false, ?int $maxDepth = null): Collection
    {
        return $this->service->getDescendants($this->model, $this->getType(), $includeSelf, $maxDepth);
    }

    /**
     * Get the direct parent.
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return null|Model The parent model or null if root
     */
    public function parent(): ?Model
    {
        return $this->service->getDirectParent($this->model, $this->getType());
    }

    /**
     * Get the direct children.
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return Collection<int, Model> Collection of direct children
     */
    public function children(): Collection
    {
        return $this->service->getDirectChildren($this->model, $this->getType());
    }

    /**
     * Get siblings (models with the same parent).
     *
     * @param bool $includeSelf Whether to include the model itself
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return Collection<int, Model> Collection of sibling models
     */
    public function siblings(bool $includeSelf = false): Collection
    {
        return $this->service->getSiblings($this->model, $this->getType(), $includeSelf);
    }

    /**
     * Check if this model is an ancestor of another.
     *
     * @param Model $model The potential descendant model
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return bool True if ancestor relationship exists
     */
    public function isAncestorOf(Model $model): bool
    {
        return $this->service->isAncestorOf($this->model, $model, $this->getType());
    }

    /**
     * Check if this model is a descendant of another.
     *
     * @param Model $model The potential ancestor model
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return bool True if descendant relationship exists
     */
    public function isDescendantOf(Model $model): bool
    {
        return $this->service->isDescendantOf($this->model, $model, $this->getType());
    }

    /**
     * Get the depth in the hierarchy.
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return int The depth level (0-based)
     */
    public function depth(): int
    {
        return $this->service->getDepth($this->model, $this->getType());
    }

    /**
     * Get the root(s) of this model's hierarchy.
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return Collection<int, Model> Collection of root models
     */
    public function roots(): Collection
    {
        return $this->service->getRoots($this->model, $this->getType());
    }

    /**
     * Build a tree structure from this model's descendants.
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return array{model: Model, children: array<int, mixed>} Nested tree structure
     */
    public function tree(): array
    {
        return $this->service->buildTree($this->model, $this->getType());
    }

    /**
     * Get the path from root to this model.
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return Collection<int, Model> Ordered collection from root to model
     */
    public function path(): Collection
    {
        return $this->service->getPath($this->model, $this->getType());
    }

    /**
     * Check if this model is in a hierarchy.
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return bool True if model is in the hierarchy
     */
    public function isInAncestry(): bool
    {
        return $this->service->isInAncestry($this->model, $this->getType());
    }

    /**
     * Check if this model is a root.
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return bool True if model is a root node
     */
    public function isRoot(): bool
    {
        return $this->service->isRoot($this->model, $this->getType());
    }

    /**
     * Check if this model is a leaf (no children).
     *
     * @throws InvalidConfigurationException If type not set
     *
     * @return bool True if model has no children
     */
    public function isLeaf(): bool
    {
        return $this->service->isLeaf($this->model, $this->getType());
    }

    /**
     * Get the hierarchy type, throwing if not set.
     *
     * @throws InvalidConfigurationException If type has not been set
     *
     * @return AncestryType|string The configured hierarchy type
     */
    private function getType(): AncestryType|string
    {
        throw_if($this->type === null, InvalidConfigurationException::missingAncestryType());

        return $this->type;
    }
}
