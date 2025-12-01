<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Concerns;

use Cline\Ancestry\Contracts\AncestryService;
use Cline\Ancestry\Contracts\AncestryType;
use Cline\Ancestry\Database\Ancestor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

use function resolve;

/**
 * Trait for models that participate in hierarchies.
 *
 * Provides convenient methods for managing hierarchical relationships
 * using the closure table pattern. Use this trait on any Eloquent model
 * that needs to participate in hierarchies.
 *
 * @mixin Model
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait HasAncestry
{
    /**
     * Get all hierarchy entries where this model is an ancestor.
     *
     * @return MorphMany<Ancestor, $this>
     */
    public function ancestryAsAncestor(): MorphMany
    {
        /** @var class-string<Ancestor> $ancestorModel */
        $ancestorModel = Config::get('ancestry.models.ancestor', Ancestor::class);

        return $this->morphMany($ancestorModel, 'ancestor');
    }

    /**
     * Get all hierarchy entries where this model is a descendant.
     *
     * @return MorphMany<Ancestor, $this>
     */
    public function ancestryAsDescendant(): MorphMany
    {
        /** @var class-string<Ancestor> $ancestorModel */
        $ancestorModel = Config::get('ancestry.models.ancestor', Ancestor::class);

        return $this->morphMany($ancestorModel, 'descendant');
    }

    /**
     * Add this model to a hierarchy.
     *
     * @param AncestryType|string $type   The hierarchy type
     * @param null|Model          $parent Optional parent model
     */
    public function addToAncestry(AncestryType|string $type, ?Model $parent = null): void
    {
        $this->getAncestryService()->addToAncestry($this, $type, $parent);
    }

    /**
     * Attach this model to a parent in an existing hierarchy.
     *
     * @param Model               $parent The parent model
     * @param AncestryType|string $type   The hierarchy type
     */
    public function attachToAncestryParent(Model $parent, AncestryType|string $type): void
    {
        $this->getAncestryService()->attachToParent($this, $parent, $type);
    }

    /**
     * Detach this model from its parent (become a root).
     *
     * @param AncestryType|string $type The hierarchy type
     */
    public function detachFromAncestryParent(AncestryType|string $type): void
    {
        $this->getAncestryService()->detachFromParent($this, $type);
    }

    /**
     * Remove this model from a hierarchy completely.
     *
     * @param AncestryType|string $type The hierarchy type
     */
    public function removeFromAncestry(AncestryType|string $type): void
    {
        $this->getAncestryService()->removeFromAncestry($this, $type);
    }

    /**
     * Move this model to a new parent.
     *
     * @param null|Model          $newParent The new parent (null to make root)
     * @param AncestryType|string $type      The hierarchy type
     */
    public function moveToAncestryParent(?Model $newParent, AncestryType|string $type): void
    {
        $this->getAncestryService()->moveToParent($this, $newParent, $type);
    }

    /**
     * Get all ancestors of this model.
     *
     * @param  AncestryType|string    $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include this model
     * @param  null|int               $maxDepth    Maximum depth to traverse
     * @return Collection<int, Model> Collection of ancestor models
     */
    public function getAncestryAncestors(
        AncestryType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection {
        return $this->getAncestryService()->getAncestors($this, $type, $includeSelf, $maxDepth);
    }

    /**
     * Get all descendants of this model.
     *
     * @param  AncestryType|string    $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include this model
     * @param  null|int               $maxDepth    Maximum depth to traverse
     * @return Collection<int, Model> Collection of descendant models
     */
    public function getAncestryDescendants(
        AncestryType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection {
        return $this->getAncestryService()->getDescendants($this, $type, $includeSelf, $maxDepth);
    }

    /**
     * Get the direct parent of this model.
     *
     * @param  AncestryType|string $type The hierarchy type
     * @return null|Model          The parent model or null if root
     */
    public function getAncestryParent(AncestryType|string $type): ?Model
    {
        return $this->getAncestryService()->getDirectParent($this, $type);
    }

    /**
     * Get the direct children of this model.
     *
     * @param  AncestryType|string    $type The hierarchy type
     * @return Collection<int, Model> Collection of direct children
     */
    public function getAncestryChildren(AncestryType|string $type): Collection
    {
        return $this->getAncestryService()->getDirectChildren($this, $type);
    }

    /**
     * Check if this model is an ancestor of another.
     *
     * @param  Model               $model The potential descendant
     * @param  AncestryType|string $type  The hierarchy type
     * @return bool                True if ancestor relationship exists
     */
    public function isAncestryAncestorOf(Model $model, AncestryType|string $type): bool
    {
        return $this->getAncestryService()->isAncestorOf($this, $model, $type);
    }

    /**
     * Check if this model is a descendant of another.
     *
     * @param  Model               $model The potential ancestor
     * @param  AncestryType|string $type  The hierarchy type
     * @return bool                True if descendant relationship exists
     */
    public function isAncestryDescendantOf(Model $model, AncestryType|string $type): bool
    {
        return $this->getAncestryService()->isDescendantOf($this, $model, $type);
    }

    /**
     * Get this model's depth in the hierarchy.
     *
     * @param  AncestryType|string $type The hierarchy type
     * @return int                 The depth level (0-based)
     */
    public function getAncestryDepth(AncestryType|string $type): int
    {
        return $this->getAncestryService()->getDepth($this, $type);
    }

    /**
     * Get the root(s) of this model's hierarchy.
     *
     * @param  AncestryType|string    $type The hierarchy type
     * @return Collection<int, Model> Collection of root models
     */
    public function getAncestryRoots(AncestryType|string $type): Collection
    {
        return $this->getAncestryService()->getRoots($this, $type);
    }

    /**
     * Build a tree from this model's descendants.
     *
     * @param  AncestryType|string                              $type The hierarchy type
     * @return array{model: Model, children: array<int, mixed>} Nested tree structure
     */
    public function buildAncestryTree(AncestryType|string $type): array
    {
        return $this->getAncestryService()->buildTree($this, $type);
    }

    /**
     * Get the path from root to this model.
     *
     * @param  AncestryType|string    $type The hierarchy type
     * @return Collection<int, Model> Ordered collection from root to model
     */
    public function getAncestryPath(AncestryType|string $type): Collection
    {
        return $this->getAncestryService()->getPath($this, $type);
    }

    /**
     * Check if this model is in a hierarchy.
     *
     * @param  AncestryType|string $type The hierarchy type
     * @return bool                True if model is in the hierarchy
     */
    public function isInAncestry(AncestryType|string $type): bool
    {
        return $this->getAncestryService()->isInAncestry($this, $type);
    }

    /**
     * Check if this model is a root in a hierarchy.
     *
     * @param  AncestryType|string $type The hierarchy type
     * @return bool                True if model is a root node
     */
    public function isAncestryRoot(AncestryType|string $type): bool
    {
        return $this->getAncestryService()->isRoot($this, $type);
    }

    /**
     * Check if this model is a leaf (has no children) in a hierarchy.
     *
     * @param  AncestryType|string $type The hierarchy type
     * @return bool                True if model has no children
     */
    public function isAncestryLeaf(AncestryType|string $type): bool
    {
        return $this->getAncestryService()->isLeaf($this, $type);
    }

    /**
     * Get siblings (models with the same parent) in a hierarchy.
     *
     * @param  AncestryType|string    $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include this model
     * @return Collection<int, Model> Collection of sibling models
     */
    public function getAncestrySiblings(AncestryType|string $type, bool $includeSelf = false): Collection
    {
        return $this->getAncestryService()->getSiblings($this, $type, $includeSelf);
    }

    /**
     * Get the ancestry service from the container.
     *
     * @return AncestryService The ancestry service instance
     */
    protected function getAncestryService(): AncestryService
    {
        return resolve(AncestryService::class);
    }
}
