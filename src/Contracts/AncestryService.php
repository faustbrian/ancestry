<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Contract for the Ancestry service.
 *
 * Defines the core operations for managing hierarchical relationships
 * using the closure table pattern.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface AncestryService
{
    /**
     * Add a model to a hierarchy under an optional parent.
     *
     * Creates the self-referencing row and all ancestor relationships.
     *
     * @param Model               $model  The model to add to the hierarchy
     * @param AncestryType|string $type   The hierarchy type
     * @param null|Model          $parent Optional parent model
     */
    public function addToAncestry(
        Model $model,
        AncestryType|string $type,
        ?Model $parent = null,
    ): void;

    /**
     * Attach a model to a parent in an existing hierarchy.
     *
     * @param Model               $model  The model to attach
     * @param Model               $parent The parent model to attach to
     * @param AncestryType|string $type   The hierarchy type
     */
    public function attachToParent(
        Model $model,
        Model $parent,
        AncestryType|string $type,
    ): void;

    /**
     * Detach a model from its parent (keeps in hierarchy as root).
     *
     * @param Model               $model The model to detach
     * @param AncestryType|string $type  The hierarchy type
     */
    public function detachFromParent(
        Model $model,
        AncestryType|string $type,
    ): void;

    /**
     * Remove a model completely from a hierarchy.
     *
     * @param Model               $model The model to remove
     * @param AncestryType|string $type  The hierarchy type
     */
    public function removeFromAncestry(
        Model $model,
        AncestryType|string $type,
    ): void;

    /**
     * Move a model to a new parent.
     *
     * @param Model               $model     The model to move
     * @param null|Model          $newParent The new parent (null to make root)
     * @param AncestryType|string $type      The hierarchy type
     */
    public function moveToParent(
        Model $model,
        ?Model $newParent,
        AncestryType|string $type,
    ): void;

    /**
     * Get all ancestors of a model.
     *
     * @param  Model                  $model       The model to get ancestors for
     * @param  AncestryType|string    $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include the model itself
     * @param  null|int               $maxDepth    Maximum depth to traverse
     * @return Collection<int, Model> Collection of ancestor models
     */
    public function getAncestors(
        Model $model,
        AncestryType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection;

    /**
     * Get all descendants of a model.
     *
     * @param  Model                  $model       The model to get descendants for
     * @param  AncestryType|string    $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include the model itself
     * @param  null|int               $maxDepth    Maximum depth to traverse
     * @return Collection<int, Model> Collection of descendant models
     */
    public function getDescendants(
        Model $model,
        AncestryType|string $type,
        bool $includeSelf = false,
        ?int $maxDepth = null,
    ): Collection;

    /**
     * Get the direct parent of a model.
     *
     * @param  Model               $model The model to get parent for
     * @param  AncestryType|string $type  The hierarchy type
     * @return null|Model          The parent model or null if root
     */
    public function getDirectParent(
        Model $model,
        AncestryType|string $type,
    ): ?Model;

    /**
     * Get the direct children of a model.
     *
     * @param  Model                  $model The model to get children for
     * @param  AncestryType|string    $type  The hierarchy type
     * @return Collection<int, Model> Collection of direct children
     */
    public function getDirectChildren(
        Model $model,
        AncestryType|string $type,
    ): Collection;

    /**
     * Check if a model is an ancestor of another.
     *
     * @param  Model               $potentialAncestor   The potential ancestor
     * @param  Model               $potentialDescendant The potential descendant
     * @param  AncestryType|string $type                The hierarchy type
     * @return bool                True if ancestor relationship exists
     */
    public function isAncestorOf(
        Model $potentialAncestor,
        Model $potentialDescendant,
        AncestryType|string $type,
    ): bool;

    /**
     * Check if a model is a descendant of another.
     *
     * @param  Model               $potentialDescendant The potential descendant
     * @param  Model               $potentialAncestor   The potential ancestor
     * @param  AncestryType|string $type                The hierarchy type
     * @return bool                True if descendant relationship exists
     */
    public function isDescendantOf(
        Model $potentialDescendant,
        Model $potentialAncestor,
        AncestryType|string $type,
    ): bool;

    /**
     * Get the depth of a model in the hierarchy.
     *
     * @param  Model               $model The model to get depth for
     * @param  AncestryType|string $type  The hierarchy type
     * @return int                 The depth level (0-based)
     */
    public function getDepth(
        Model $model,
        AncestryType|string $type,
    ): int;

    /**
     * Get the root ancestor(s) of a model.
     *
     * @param  Model                  $model The model to get roots for
     * @param  AncestryType|string    $type  The hierarchy type
     * @return Collection<int, Model> Collection of root models
     */
    public function getRoots(
        Model $model,
        AncestryType|string $type,
    ): Collection;

    /**
     * Build a tree structure from a model's descendants.
     *
     * @param  Model                                            $model The root model
     * @param  AncestryType|string                              $type  The hierarchy type
     * @return array{model: Model, children: array<int, mixed>} Nested tree structure
     */
    public function buildTree(
        Model $model,
        AncestryType|string $type,
    ): array;

    /**
     * Get the full path from root to model.
     *
     * @param  Model                  $model The model to get path for
     * @param  AncestryType|string    $type  The hierarchy type
     * @return Collection<int, Model> Ordered collection from root to model
     */
    public function getPath(
        Model $model,
        AncestryType|string $type,
    ): Collection;

    /**
     * Check if a model is in a hierarchy.
     *
     * @param  Model               $model The model to check
     * @param  AncestryType|string $type  The hierarchy type
     * @return bool                True if model is in the hierarchy
     */
    public function isInAncestry(
        Model $model,
        AncestryType|string $type,
    ): bool;

    /**
     * Check if a model is a root in a hierarchy.
     *
     * @param  Model               $model The model to check
     * @param  AncestryType|string $type  The hierarchy type
     * @return bool                True if model is a root node
     */
    public function isRoot(
        Model $model,
        AncestryType|string $type,
    ): bool;

    /**
     * Check if a model is a leaf (has no children) in a hierarchy.
     *
     * @param  Model               $model The model to check
     * @param  AncestryType|string $type  The hierarchy type
     * @return bool                True if model has no children
     */
    public function isLeaf(
        Model $model,
        AncestryType|string $type,
    ): bool;

    /**
     * Get siblings (models with the same parent) in a hierarchy.
     *
     * @param  Model                  $model       The model to get siblings for
     * @param  AncestryType|string    $type        The hierarchy type
     * @param  bool                   $includeSelf Whether to include the model itself
     * @return Collection<int, Model> Collection of sibling models
     */
    public function getSiblings(
        Model $model,
        AncestryType|string $type,
        bool $includeSelf = false,
    ): Collection;

    /**
     * Get all root nodes for a hierarchy type.
     *
     * @param  AncestryType|string    $type The hierarchy type
     * @return Collection<int, Model> Collection of root nodes
     */
    public function getRootNodes(AncestryType|string $type): Collection;
}
