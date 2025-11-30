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
 * @method static void                                             addToAncestry(Model $model, AncestryType|string $type, ?Model $parent = null)
 * @method static void                                             attachToParent(Model $model, Model $parent, AncestryType|string $type)
 * @method static array{model: Model, children: array<int, mixed>} buildTree(Model $model, AncestryType|string $type)
 * @method static void                                             detachFromParent(Model $model, AncestryType|string $type)
 * @method static ForModelConductor                                for(Model $model)
 * @method static Collection<int, Model>                           getAncestors(Model $model, AncestryType|string $type, bool $includeSelf = false, ?int $maxDepth = null)
 * @method static int                                              getDepth(Model $model, AncestryType|string $type)
 * @method static Collection<int, Model>                           getDescendants(Model $model, AncestryType|string $type, bool $includeSelf = false, ?int $maxDepth = null)
 * @method static Collection<int, Model>                           getDirectChildren(Model $model, AncestryType|string $type)
 * @method static ?Model                                           getDirectParent(Model $model, AncestryType|string $type)
 * @method static Collection<int, Model>                           getPath(Model $model, AncestryType|string $type)
 * @method static Collection<int, Model>                           getRootNodes(AncestryType|string $type)
 * @method static Collection<int, Model>                           getRoots(Model $model, AncestryType|string $type)
 * @method static Collection<int, Model>                           getSiblings(Model $model, AncestryType|string $type, bool $includeSelf = false)
 * @method static bool                                             isAncestorOf(Model $potentialAncestor, Model $potentialDescendant, AncestryType|string $type)
 * @method static bool                                             isDescendantOf(Model $potentialDescendant, Model $potentialAncestor, AncestryType|string $type)
 * @method static bool                                             isInAncestry(Model $model, AncestryType|string $type)
 * @method static bool                                             isLeaf(Model $model, AncestryType|string $type)
 * @method static bool                                             isRoot(Model $model, AncestryType|string $type)
 * @method static void                                             moveToParent(Model $model, ?Model $newParent, AncestryType|string $type)
 * @method static TypeConductor                                    ofType(AncestryType|string $type)
 * @method static void                                             removeFromAncestry(Model $model, AncestryType|string $type)
 *
 * @see AncestryManager
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Ancestry extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return AncestryManager::class;
    }
}
