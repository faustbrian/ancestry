<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Contracts;

use Cline\Ancestry\Database\Ancestor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Contract for models that participate in hierarchies.
 *
 * Implement this interface on Eloquent models that need hierarchical
 * relationships. Use the HasAncestry trait for the default implementation.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface HasAncestry
{
    /**
     * Get all hierarchy entries where this model is an ancestor.
     *
     * @return MorphMany<Ancestor, Model>
     */
    public function ancestryAsAncestor(): MorphMany;

    /**
     * Get all hierarchy entries where this model is a descendant.
     *
     * @return MorphMany<Ancestor, Model>
     */
    public function ancestryAsDescendant(): MorphMany;

    /**
     * Add this model to a hierarchy.
     *
     * @param AncestryType|string $type   The hierarchy type
     * @param null|Model          $parent Optional parent model
     */
    public function addToAncestry(AncestryType|string $type, ?Model $parent = null): void;

    /**
     * Attach this model to a parent in an existing hierarchy.
     *
     * @param Model               $parent The parent model
     * @param AncestryType|string $type   The hierarchy type
     */
    public function attachToAncestryParent(Model $parent, AncestryType|string $type): void;

    /**
     * Detach this model from its parent (become a root).
     *
     * @param AncestryType|string $type The hierarchy type
     */
    public function detachFromAncestryParent(AncestryType|string $type): void;

    /**
     * Remove this model from a hierarchy completely.
     *
     * @param AncestryType|string $type The hierarchy type
     */
    public function removeFromAncestry(AncestryType|string $type): void;

    /**
     * Move this model to a new parent.
     *
     * @param null|Model          $newParent The new parent (null to make root)
     * @param AncestryType|string $type      The hierarchy type
     */
    public function moveToAncestryParent(?Model $newParent, AncestryType|string $type): void;

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
    ): Collection;

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
    ): Collection;

    /**
     * Get the direct parent of this model.
     *
     * @param  AncestryType|string $type The hierarchy type
     * @return null|Model          The parent model or null if root
     */
    public function getAncestryParent(AncestryType|string $type): ?Model;

    /**
     * Get the direct children of this model.
     *
     * @param  AncestryType|string    $type The hierarchy type
     * @return Collection<int, Model> Collection of direct children
     */
    public function getAncestryChildren(AncestryType|string $type): Collection;

    /**
     * Check if this model is an ancestor of another.
     *
     * @param  Model               $model The potential descendant
     * @param  AncestryType|string $type  The hierarchy type
     * @return bool                True if ancestor relationship exists
     */
    public function isAncestryAncestorOf(Model $model, AncestryType|string $type): bool;

    /**
     * Check if this model is a descendant of another.
     *
     * @param  Model               $model The potential ancestor
     * @param  AncestryType|string $type  The hierarchy type
     * @return bool                True if descendant relationship exists
     */
    public function isAncestryDescendantOf(Model $model, AncestryType|string $type): bool;

    /**
     * Get this model's depth in the hierarchy.
     *
     * @param  AncestryType|string $type The hierarchy type
     * @return int                 The depth level (0-based)
     */
    public function getAncestryDepth(AncestryType|string $type): int;

    /**
     * Get the root(s) of this model's hierarchy.
     *
     * @param  AncestryType|string    $type The hierarchy type
     * @return Collection<int, Model> Collection of root models
     */
    public function getAncestryRoots(AncestryType|string $type): Collection;

    /**
     * Build a tree from this model's descendants.
     *
     * @param  AncestryType|string                              $type The hierarchy type
     * @return array{model: Model, children: array<int, mixed>} Nested tree structure
     */
    public function buildAncestryTree(AncestryType|string $type): array;

    /**
     * Get the path from root to this model.
     *
     * @param  AncestryType|string    $type The hierarchy type
     * @return Collection<int, Model> Ordered collection from root to model
     */
    public function getAncestryPath(AncestryType|string $type): Collection;
}
