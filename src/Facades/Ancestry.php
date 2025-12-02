<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Facades;

use Cline\Ancestry\AncestryManager;
use Cline\Ancestry\Conductors\ForModelConductor;
use Cline\Ancestry\Conductors\TypeConductor;
use Cline\Ancestry\Contracts\AncestryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Ancestry hierarchy management system.
 *
 * Provides static access to hierarchy operations for managing parent-child relationships,
 * traversing ancestor-descendant trees, and querying hierarchical structures across
 * multiple hierarchy types within Laravel Eloquent models.
 *
 * @method static void                                             addToAncestry(Model $model, AncestryType|string $type, ?Model $parent = null)                             Add a model to a hierarchy with an optional parent
 * @method static void                                             attachToParent(Model $model, Model $parent, AncestryType|string $type)                                    Attach a model to a specific parent in a hierarchy
 * @method static array{model: Model, children: array<int, mixed>} buildTree(Model $model, AncestryType|string $type)                                                        Build a nested tree structure starting from a model
 * @method static void                                             detachFromParent(Model $model, AncestryType|string $type)                                                 Detach a model from its parent in a hierarchy
 * @method static ForModelConductor                                for(Model $model)                                                                                         Create a conductor instance scoped to a specific model
 * @method static Collection<int, Model>                           getAncestors(Model $model, AncestryType|string $type, bool $includeSelf = false, ?int $maxDepth = null)   Retrieve all ancestors of a model in a hierarchy
 * @method static int                                              getDepth(Model $model, AncestryType|string $type)                                                         Calculate the depth level of a model in a hierarchy
 * @method static Collection<int, Model>                           getDescendants(Model $model, AncestryType|string $type, bool $includeSelf = false, ?int $maxDepth = null) Retrieve all descendants of a model in a hierarchy
 * @method static Collection<int, Model>                           getDirectChildren(Model $model, AncestryType|string $type)                                                Retrieve immediate child nodes of a model
 * @method static ?Model                                           getDirectParent(Model $model, AncestryType|string $type)                                                  Retrieve the immediate parent of a model, or null if root
 * @method static Collection<int, Model>                           getPath(Model $model, AncestryType|string $type)                                                          Retrieve the path from root to a model
 * @method static Collection<int, Model>                           getRootNodes(AncestryType|string $type)                                                                   Retrieve all root nodes for a hierarchy type
 * @method static Collection<int, Model>                           getRoots(Model $model, AncestryType|string $type)                                                         Retrieve root nodes related to a specific model
 * @method static Collection<int, Model>                           getSiblings(Model $model, AncestryType|string $type, bool $includeSelf = false)                           Retrieve sibling nodes sharing the same parent
 * @method static bool                                             isAncestorOf(Model $potentialAncestor, Model $potentialDescendant, AncestryType|string $type)             Check if a model is an ancestor of another model
 * @method static bool                                             isDescendantOf(Model $potentialDescendant, Model $potentialAncestor, AncestryType|string $type)           Check if a model is a descendant of another model
 * @method static bool                                             isInAncestry(Model $model, AncestryType|string $type)                                                     Check if a model is part of a hierarchy
 * @method static bool                                             isLeaf(Model $model, AncestryType|string $type)                                                           Check if a model is a leaf node with no children
 * @method static bool                                             isRoot(Model $model, AncestryType|string $type)                                                           Check if a model is a root node with no parent
 * @method static void                                             moveToParent(Model $model, ?Model $newParent, AncestryType|string $type)                                  Move a model to a new parent in a hierarchy
 * @method static TypeConductor                                    ofType(AncestryType|string $type)                                                                         Create a conductor instance scoped to a specific hierarchy type
 * @method static void                                             removeFromAncestry(Model $model, AncestryType|string $type)                                               Remove a model from a hierarchy entirely
 *
 * @see AncestryManager
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Ancestry extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * Returns the fully qualified class name of the AncestryManager that this facade
     * provides access to, allowing Laravel's service container to resolve the
     * underlying implementation.
     *
     * @return string The fully qualified class name of the AncestryManager
     */
    protected static function getFacadeAccessor(): string
    {
        return AncestryManager::class;
    }
}
