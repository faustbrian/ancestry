<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry;

use Cline\Ancestry\Contracts\AncestryService as AncestryServiceContract;
use Cline\Ancestry\Contracts\AncestryType;
use Cline\Ancestry\Database\Ancestor;
use Cline\Ancestry\Database\ModelRegistry;
use Cline\Ancestry\Events\NodeAttached;
use Cline\Ancestry\Events\NodeDetached;
use Cline\Ancestry\Events\NodeMoved;
use Cline\Ancestry\Events\NodeRemoved;
use Cline\Ancestry\Exceptions\CircularReferenceException;
use Cline\Ancestry\Exceptions\MaxDepthExceededException;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function collect;

/**
 * Core service for managing hierarchical relationships using closure tables.
 *
 * The closure table pattern stores all ancestor-descendant relationships explicitly,
 * enabling O(1) queries for ancestors and descendants without recursion limits.
 * This service handles all hierarchy operations including adding, moving, and
 * removing nodes, as well as querying relationships.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
#[Singleton()]
final readonly class AncestryService implements AncestryServiceContract
{
    public function __construct(
        private ModelRegistry $registry,
    ) {}

    /**
     * Add a model to a hierarchy under an optional parent.
     * Creates the self-referencing row and all ancestor relationships.
     */
    public function addToAncestry(
        Model $model,
        AncestryType|string $type,
        ?Model $parent = null,
    ): void {
        $typeValue = $this->resolveType($type);

        DB::transaction(function () use ($model, $typeValue, $parent): void {
            $morphClass = $model->getMorphClass();
            $modelKey = $this->registry->getModelKeyValue($model);

            // Self-referencing row (depth 0) - every node references itself
            $this->createAncestor([
                'ancestor_type' => $morphClass,
                'ancestor_id' => $modelKey,
                'descendant_type' => $morphClass,
                'descendant_id' => $modelKey,
                'depth' => 0,
                'type' => $typeValue,
            ]);

            if ($parent instanceof Model) {
                $this->attachToParent($model, $parent, $typeValue);
            }
        });
    }

    /**
     * Attach a model to a parent in an existing hierarchy.
     */
    public function attachToParent(
        Model $model,
        Model $parent,
        AncestryType|string $type,
    ): void {
        $typeValue = $this->resolveType($type);

        DB::transaction(function () use ($model, $parent, $typeValue): void {
            $modelMorph = $model->getMorphClass();
            $modelKey = $this->registry->getModelKeyValue($model);
            $parentMorph = $parent->getMorphClass();
            $parentKey = $this->registry->getModelKeyValue($parent);

            // Prevent circular references
            if ($this->isAncestorOf($model, $parent, $typeValue)) {
                throw CircularReferenceException::detected($model, $parent);
            }

            // Check max depth
            /** @var null|int $maxDepth */
            $maxDepth = Config::get('ancestry.max_depth');

            if ($maxDepth !== null) {
                $parentDepth = $this->getDepth($parent, $typeValue);

                if ($parentDepth >= $maxDepth) {
                    throw MaxDepthExceededException::exceeded($maxDepth);
                }
            }

            // Ensure self-reference exists for the model
            $this->ensureSelfReference($model, $typeValue);

            // Ensure parent is in hierarchy (has self-reference)
            $this->ensureSelfReference($parent, $typeValue);

            // Get all ancestors of the parent (including parent's self-reference)
            $parentAncestors = $this->getAncestorModel()::query()
                ->where('descendant_type', $parentMorph)
                ->where('descendant_id', $parentKey)
                ->where('type', $typeValue)
                ->get();

            // Create paths from all parent's ancestors to this model
            foreach ($parentAncestors as $ancestorRow) {
                $this->createAncestor([
                    'ancestor_type' => $ancestorRow->ancestor_type,
                    'ancestor_id' => $ancestorRow->ancestor_id,
                    'descendant_type' => $modelMorph,
                    'descendant_id' => $modelKey,
                    'depth' => $ancestorRow->depth + 1,
                    'type' => $typeValue,
                ]);
            }

            $this->dispatchEvent(
                new NodeAttached($model, $parent, $typeValue),
            );
        });
    }

    /**
     * Detach a model from its parent (keeps in hierarchy as root).
     */
    public function detachFromParent(
        Model $model,
        AncestryType|string $type,
    ): void {
        $typeValue = $this->resolveType($type);

        DB::transaction(function () use ($model, $typeValue): void {
            $morphClass = $model->getMorphClass();
            $modelKey = $this->registry->getModelKeyValue($model);

            // Get the parent before detaching for the event
            $parent = $this->getDirectParent($model, $typeValue);

            // Remove all ancestor paths except self-reference
            $this->getAncestorModel()::query()
                ->where('descendant_type', $morphClass)
                ->where('descendant_id', $modelKey)
                ->where('type', $typeValue)
                ->where('depth', '>', 0)
                ->delete();

            if ($parent instanceof Model) {
                $this->dispatchEvent(
                    new NodeDetached($model, $parent, $typeValue),
                );
            }
        });
    }

    /**
     * Remove a model completely from a hierarchy.
     * This also removes ancestor paths for all descendants that went through this model.
     */
    public function removeFromAncestry(
        Model $model,
        AncestryType|string $type,
    ): void {
        $typeValue = $this->resolveType($type);

        DB::transaction(function () use ($model, $typeValue): void {
            $morphClass = $model->getMorphClass();
            $modelKey = $this->registry->getModelKeyValue($model);

            // Get all descendants (they will lose their ancestor paths through this model)
            $descendants = $this->getDescendants($model, $typeValue, includeSelf: false);

            // Get all ancestors of this model (to remove paths from ancestors to descendants)
            $ancestors = $this->getAncestors($model, $typeValue, includeSelf: false);

            // For each descendant, remove paths to all ancestors of the removed model
            foreach ($descendants as $descendant) {
                $descMorph = $descendant->getMorphClass();
                $descKey = $this->registry->getModelKeyValue($descendant);

                foreach ($ancestors as $ancestor) {
                    $this->getAncestorModel()::query()
                        ->where('ancestor_type', $ancestor->getMorphClass())
                        ->where('ancestor_id', $this->registry->getModelKeyValue($ancestor))
                        ->where('descendant_type', $descMorph)
                        ->where('descendant_id', $descKey)
                        ->where('type', $typeValue)
                        ->delete();
                }
            }

            // Remove all paths where this model is ancestor or descendant
            $this->getAncestorModel()::query()
                ->where('type', $typeValue)
                ->where(function ($query) use ($morphClass, $modelKey): void {
                    $query->where(function ($q) use ($morphClass, $modelKey): void {
                        $q->where('ancestor_type', $morphClass)
                            ->where('ancestor_id', $modelKey);
                    })->orWhere(function ($q) use ($morphClass, $modelKey): void {
                        $q->where('descendant_type', $morphClass)
                            ->where('descendant_id', $modelKey);
                    });
                })
                ->delete();

            $this->dispatchEvent(
                new NodeRemoved($model, $typeValue),
            );
        });
    }

    /**
     * Move a model to a new parent.
     */
    public function moveToParent(
        Model $model,
        ?Model $newParent,
        AncestryType|string $type,
    ): void {
        $typeValue = $this->resolveType($type);

        // Check for circular reference: newParent cannot be a descendant of model
        if ($newParent instanceof Model && $this->isDescendantOf($newParent, $model, $typeValue)) {
            throw CircularReferenceException::detected($model, $newParent);
        }

        DB::transaction(function () use ($model, $newParent, $typeValue): void {
            $oldParent = $this->getDirectParent($model, $typeValue);

            // Get all descendants and cache their direct parent relationships BEFORE detaching
            $descendants = $this->getDescendants($model, $typeValue, includeSelf: false);
            $parentMap = [];

            foreach ($descendants as $descendant) {
                $directParent = $this->getDirectParent($descendant, $typeValue);

                if ($directParent instanceof Model) {
                    $key = $descendant->getMorphClass().':'.$this->registry->getModelKeyValue($descendant);
                    $parentMap[$key] = $directParent;
                }
            }

            // Detach from current parent
            $this->detachFromParent($model, $typeValue);

            // Also detach all descendants' ancestor paths that go through this model
            foreach ($descendants as $descendant) {
                $this->getAncestorModel()::query()
                    ->where('descendant_type', $descendant->getMorphClass())
                    ->where('descendant_id', $this->registry->getModelKeyValue($descendant))
                    ->where('type', $typeValue)
                    ->where('depth', '>', 0)
                    ->delete();
            }

            // Attach to new parent if provided
            if ($newParent instanceof Model) {
                $this->attachToParent($model, $newParent, $typeValue);
            }

            // Rebuild paths for descendants using cached parent map
            foreach ($descendants as $descendant) {
                $key = $descendant->getMorphClass().':'.$this->registry->getModelKeyValue($descendant);
                $directParent = $parentMap[$key] ?? null;

                if ($directParent instanceof Model) {
                    $this->attachToParent($descendant, $directParent, $typeValue);
                }
            }

            $this->dispatchEvent(
                new NodeMoved($model, $oldParent, $newParent, $typeValue),
            );
        });
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
        $typeValue = $this->resolveType($type);
        $morphClass = $model->getMorphClass();
        $modelKey = $this->registry->getModelKeyValue($model);

        $query = $this->getAncestorModel()::query()
            ->where('descendant_type', $morphClass)
            ->where('descendant_id', $modelKey)
            ->where('type', $typeValue);

        if (!$includeSelf) {
            $query->where('depth', '>', 0);
        }

        if ($maxDepth !== null) {
            $query->where('depth', '<=', $maxDepth);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Ancestor> $results */
        $results = $query->orderBy('depth')->get();

        /** @var Collection<int, Model> */
        return $results
            ->map(fn (Ancestor $h): ?Model => $h->ancestor())
            ->filter()
            ->values();
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
        $typeValue = $this->resolveType($type);
        $morphClass = $model->getMorphClass();
        $modelKey = $this->registry->getModelKeyValue($model);

        $query = $this->getAncestorModel()::query()
            ->where('ancestor_type', $morphClass)
            ->where('ancestor_id', $modelKey)
            ->where('type', $typeValue);

        if (!$includeSelf) {
            $query->where('depth', '>', 0);
        }

        if ($maxDepth !== null) {
            $query->where('depth', '<=', $maxDepth);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Ancestor> $results */
        $results = $query->orderBy('depth')->get();

        /** @var Collection<int, Model> */
        return $results
            ->map(fn (Ancestor $h): ?Model => $h->descendant())
            ->filter()
            ->values();
    }

    /**
     * Get the direct parent of a model.
     */
    public function getDirectParent(
        Model $model,
        AncestryType|string $type,
    ): ?Model {
        $typeValue = $this->resolveType($type);
        $morphClass = $model->getMorphClass();
        $modelKey = $this->registry->getModelKeyValue($model);

        $hierarchy = $this->getAncestorModel()::query()
            ->where('descendant_type', $morphClass)
            ->where('descendant_id', $modelKey)
            ->where('type', $typeValue)
            ->where('depth', 1)
            ->first();

        return $hierarchy?->ancestor();
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
        $typeValue = $this->resolveType($type);
        $morphClass = $model->getMorphClass();
        $modelKey = $this->registry->getModelKeyValue($model);

        /** @var Collection<int, Model> */
        return $this->getAncestorModel()::query()
            ->where('ancestor_type', $morphClass)
            ->where('ancestor_id', $modelKey)
            ->where('type', $typeValue)
            ->where('depth', 1)
            ->get()
            ->map(fn (Ancestor $h): ?Model => $h->descendant())
            ->filter()
            ->values();
    }

    /**
     * Check if a model is an ancestor of another.
     */
    public function isAncestorOf(
        Model $potentialAncestor,
        Model $potentialDescendant,
        AncestryType|string $type,
    ): bool {
        $typeValue = $this->resolveType($type);

        return $this->getAncestorModel()::query()
            ->where('ancestor_type', $potentialAncestor->getMorphClass())
            ->where('ancestor_id', $this->registry->getModelKeyValue($potentialAncestor))
            ->where('descendant_type', $potentialDescendant->getMorphClass())
            ->where('descendant_id', $this->registry->getModelKeyValue($potentialDescendant))
            ->where('type', $typeValue)
            ->where('depth', '>', 0)
            ->exists();
    }

    /**
     * Check if a model is a descendant of another.
     */
    public function isDescendantOf(
        Model $potentialDescendant,
        Model $potentialAncestor,
        AncestryType|string $type,
    ): bool {
        return $this->isAncestorOf($potentialAncestor, $potentialDescendant, $type);
    }

    /**
     * Get the depth of a model in the hierarchy.
     */
    public function getDepth(
        Model $model,
        AncestryType|string $type,
    ): int {
        $typeValue = $this->resolveType($type);
        $morphClass = $model->getMorphClass();
        $modelKey = $this->registry->getModelKeyValue($model);

        /** @var null|int $maxDepth */
        $maxDepth = $this->getAncestorModel()::query()
            ->where('descendant_type', $morphClass)
            ->where('descendant_id', $modelKey)
            ->where('type', $typeValue)
            ->max('depth');

        return $maxDepth ?? 0;
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
        $typeValue = $this->resolveType($type);
        $morphClass = $model->getMorphClass();
        $modelKey = $this->registry->getModelKeyValue($model);

        // Get the maximum depth for this model
        $maxDepth = $this->getDepth($model, $typeValue);

        if ($maxDepth === 0) {
            // Model is its own root
            return collect([$model]);
        }

        /** @var Collection<int, Model> */
        return $this->getAncestorModel()::query()
            ->where('descendant_type', $morphClass)
            ->where('descendant_id', $modelKey)
            ->where('type', $typeValue)
            ->where('depth', $maxDepth)
            ->get()
            ->map(fn (Ancestor $h): ?Model => $h->ancestor())
            ->filter()
            ->values();
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
        $typeValue = $this->resolveType($type);
        $children = $this->getDirectChildren($model, $typeValue);

        return [
            'model' => $model,
            'children' => $children->map(fn (Model $child): array => $this->buildTree($child, $typeValue))->all(),
        ];
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
        return $this->getAncestors($model, $type, includeSelf: true)
            ->sortByDesc(fn ($ancestor, $key): int => $key)
            ->values();
    }

    /**
     * Check if a model is in a hierarchy.
     */
    public function isInAncestry(
        Model $model,
        AncestryType|string $type,
    ): bool {
        $typeValue = $this->resolveType($type);
        $morphClass = $model->getMorphClass();
        $modelKey = $this->registry->getModelKeyValue($model);

        return $this->getAncestorModel()::query()
            ->where('descendant_type', $morphClass)
            ->where('descendant_id', $modelKey)
            ->where('type', $typeValue)
            ->where('depth', 0)
            ->exists();
    }

    /**
     * Check if a model is a root in a hierarchy.
     */
    public function isRoot(
        Model $model,
        AncestryType|string $type,
    ): bool {
        $typeValue = $this->resolveType($type);

        // A root has no parents (only self-reference)
        return $this->isInAncestry($model, $typeValue)
            && $this->getDepth($model, $typeValue) === 0;
    }

    /**
     * Check if a model is a leaf (has no children) in a hierarchy.
     */
    public function isLeaf(
        Model $model,
        AncestryType|string $type,
    ): bool {
        $typeValue = $this->resolveType($type);
        $morphClass = $model->getMorphClass();
        $modelKey = $this->registry->getModelKeyValue($model);

        // A leaf has no children (no descendants at depth 1)
        return !$this->getAncestorModel()::query()
            ->where('ancestor_type', $morphClass)
            ->where('ancestor_id', $modelKey)
            ->where('type', $typeValue)
            ->where('depth', 1)
            ->exists();
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
        $typeValue = $this->resolveType($type);
        $parent = $this->getDirectParent($model, $typeValue);

        if (!$parent instanceof Model) {
            // Model is a root, siblings are other roots
            return $this->getRootNodes($typeValue)
                ->unless($includeSelf, fn (Collection $c) => $c->reject(
                    fn (Model $m): bool => $m->getMorphClass() === $model->getMorphClass()
                        && $this->registry->getModelKeyValue($m) === $this->registry->getModelKeyValue($model),
                ));
        }

        $siblings = $this->getDirectChildren($parent, $typeValue);

        if (!$includeSelf) {
            return $siblings->reject(
                fn (Model $m): bool => $m->getMorphClass() === $model->getMorphClass()
                    && $this->registry->getModelKeyValue($m) === $this->registry->getModelKeyValue($model),
            );
        }

        return $siblings;
    }

    /**
     * Get all root nodes for a hierarchy type.
     *
     * @return Collection<int, Model>
     */
    public function getRootNodes(AncestryType|string $type): Collection
    {
        $typeValue = $this->resolveType($type);
        $ancestorModel = $this->getAncestorModel();
        $table = $ancestorModel->getTable();

        // Roots are nodes that only have self-references (depth 0) and no parent references
        /** @var \Illuminate\Database\Eloquent\Collection<int, Ancestor> $results */
        $results = $ancestorModel::query()
            ->where('type', $typeValue)
            ->where('depth', 0)
            ->whereNotExists(function (QueryBuilder $query) use ($typeValue, $table): void {
                $query->select(DB::raw(1))
                    ->from($table.' as h2')
                    ->whereColumn('h2.descendant_type', $table.'.descendant_type')
                    ->whereColumn('h2.descendant_id', $table.'.descendant_id')
                    ->where('h2.type', $typeValue)
                    ->where('h2.depth', '>', 0);
            })
            ->get();

        /** @var Collection<int, Model> */
        return $results
            ->map(fn (Ancestor $h): ?Model => $h->descendant())
            ->filter()
            ->values();
    }

    /**
     * Resolve the hierarchy type to a string value.
     */
    private function resolveType(AncestryType|string $type): string
    {
        return $type instanceof AncestryType ? $type->value() : $type;
    }

    /**
     * Get the configured Ancestor model class.
     */
    private function getAncestorModel(): Ancestor
    {
        /** @var class-string<Ancestor> $class */
        $class = Config::get('ancestry.models.ancestor', Ancestor::class);

        return new $class();
    }

    /**
     * Create a hierarchy entry.
     *
     * @param array<string, mixed> $attributes
     */
    private function createAncestor(array $attributes): Ancestor
    {
        $model = $this->getAncestorModel();
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    /**
     * Ensure a self-reference exists for a model.
     */
    private function ensureSelfReference(Model $model, string $type): void
    {
        $morphClass = $model->getMorphClass();
        $modelKey = $this->registry->getModelKeyValue($model);

        $exists = $this->getAncestorModel()::query()
            ->where('ancestor_type', $morphClass)
            ->where('ancestor_id', $modelKey)
            ->where('descendant_type', $morphClass)
            ->where('descendant_id', $modelKey)
            ->where('type', $type)
            ->exists();

        if (!$exists) {
            $this->createAncestor([
                'ancestor_type' => $morphClass,
                'ancestor_id' => $modelKey,
                'descendant_type' => $morphClass,
                'descendant_id' => $modelKey,
                'depth' => 0,
                'type' => $type,
            ]);
        }
    }

    /**
     * Dispatch an event if events are enabled.
     */
    private function dispatchEvent(object $event): void
    {
        if (Config::get('ancestry.events.enabled', true)) {
            Event::dispatch($event);
        }
    }
}
