<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Concerns;

use Cline\Ancestry\Contracts\AncestryType;
use Cline\Ancestry\Database\AncestorSnapshot;
use Cline\Ancestry\Events\SnapshotCleared;
use Cline\Ancestry\Events\SnapshotCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

use function count;

/**
 * Trait for models that can have hierarchy snapshots attached.
 *
 * Snapshots capture the full hierarchy chain at a specific point in time,
 * preserving historical relationships even when hierarchies change.
 *
 * @mixin Model
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait HasAncestrySnapshots
{
    /**
     * Get all hierarchy snapshots for this model.
     *
     * @return MorphMany<AncestorSnapshot, $this>
     */
    public function ancestrySnapshots(): MorphMany
    {
        /** @var class-string<AncestorSnapshot> $snapshotModel */
        $snapshotModel = Config::get('ancestry.snapshots.model', AncestorSnapshot::class);

        return $this->morphMany($snapshotModel, 'context');
    }

    /**
     * Snapshot the current hierarchy chain for a given node and type.
     *
     * Captures the full ancestor chain at this point in time for historical
     * preservation. If snapshots already exist for this type, they are replaced.
     * Useful for maintaining historical references when hierarchies change.
     *
     * @param Model               $node The node whose ancestors to snapshot (must use HasAncestry trait)
     * @param AncestryType|string $type The hierarchy type
     */
    public function snapshotAncestry(Model $node, AncestryType|string $type): void
    {
        $typeValue = $type instanceof AncestryType ? $type->value() : $type;

        // Check if snapshots are enabled
        if (!Config::get('ancestry.snapshots.enabled', true)) {
            return;
        }

        // Delete existing snapshots for this context+type
        $this->ancestrySnapshots()
            ->where('type', $typeValue)
            ->delete();

        // Get all ancestors including self (the node at depth 0)
        // Already ordered by depth: 0=self, 1=parent, 2=grandparent, etc.
        /** @var Collection<int, Model> $ancestors */
        $ancestors = $node->getAncestryAncestors($type, includeSelf: true); // @phpstan-ignore method.notFound (method exists via HasAncestry trait)

        // Create snapshots for each ancestor at their depth
        /** @var array<int, AncestorSnapshot> $createdSnapshots */
        $createdSnapshots = [];

        /** @var Model $ancestor */
        foreach ($ancestors->values() as $depth => $ancestor) {
            $snapshot = $this->ancestrySnapshots()->create([
                'type' => $typeValue,
                'depth' => $depth,
                'ancestor_id' => $ancestor->getKey(),
            ]);
            $createdSnapshots[] = $snapshot;
        }

        // Dispatch event if events are enabled
        if (Config::get('ancestry.events.enabled', true)) {
            Event::dispatch(
                new SnapshotCreated(
                    context: $this,
                    type: $typeValue,
                    count: count($createdSnapshots),
                    snapshots: $createdSnapshots,
                ),
            );
        }
    }

    /**
     * Get snapshots for a specific hierarchy type.
     *
     * Returns all captured snapshots ordered by depth (0=self, 1=parent, etc.).
     *
     * @param  AncestryType|string               $type The hierarchy type
     * @return Collection<int, AncestorSnapshot> Collection of snapshot records
     */
    public function getAncestrySnapshots(AncestryType|string $type): Collection
    {
        $typeValue = $type instanceof AncestryType ? $type->value() : $type;

        /** @var Collection<int, AncestorSnapshot> */
        return $this->ancestrySnapshots()
            ->where('type', $typeValue)
            ->orderBy('depth')
            ->get();
    }

    /**
     * Check if snapshots exist for a specific hierarchy type.
     *
     * @param  AncestryType|string $type The hierarchy type
     * @return bool                True if snapshots exist
     */
    public function hasAncestrySnapshots(AncestryType|string $type): bool
    {
        $typeValue = $type instanceof AncestryType ? $type->value() : $type;

        return $this->ancestrySnapshots()
            ->where('type', $typeValue)
            ->exists();
    }

    /**
     * Clear all snapshots for a specific hierarchy type.
     *
     * Deletes all snapshot records and dispatches a cleared event if enabled.
     *
     * @param AncestryType|string $type The hierarchy type
     */
    public function clearAncestrySnapshots(AncestryType|string $type): void
    {
        $typeValue = $type instanceof AncestryType ? $type->value() : $type;

        $count = $this->ancestrySnapshots()
            ->where('type', $typeValue)
            ->count();

        $this->ancestrySnapshots()
            ->where('type', $typeValue)
            ->delete();

        // Dispatch event if events are enabled and snapshots were cleared
        if ($count > 0 && Config::get('ancestry.events.enabled', true)) {
            Event::dispatch(
                new SnapshotCleared(
                    context: $this,
                    type: $typeValue,
                    count: $count,
                ),
            );
        }
    }

    /**
     * Get the snapshot at a specific depth.
     *
     * @param  AncestryType|string   $type  The hierarchy type
     * @param  int                   $depth The depth level (0=self, 1=parent, etc.)
     * @return null|AncestorSnapshot The snapshot or null if not found
     */
    public function getAncestrySnapshotAtDepth(AncestryType|string $type, int $depth): ?AncestorSnapshot
    {
        $typeValue = $type instanceof AncestryType ? $type->value() : $type;

        return $this->ancestrySnapshots()
            ->where('type', $typeValue)
            ->where('depth', $depth)
            ->first();
    }

    /**
     * Get the direct node from snapshot (depth 0).
     *
     * Convenience method for retrieving the snapshot of the node itself.
     *
     * @param  AncestryType|string   $type The hierarchy type
     * @return null|AncestorSnapshot The snapshot or null if not found
     */
    public function getDirectAncestrySnapshot(AncestryType|string $type): ?AncestorSnapshot
    {
        return $this->getAncestrySnapshotAtDepth($type, 0);
    }

    /**
     * Get all ancestor IDs from snapshots as an array.
     *
     * Useful for building queries or exporting snapshot data.
     *
     * @param  AncestryType|string $type The hierarchy type
     * @return array<int, mixed>   Array of ancestor IDs
     */
    public function getAncestrySnapshotAncestorIds(AncestryType|string $type): array
    {
        return $this->getAncestrySnapshots($type)
            ->pluck('ancestor_id')
            ->toArray();
    }

    /**
     * Get snapshots as an array suitable for export/serialization.
     *
     * Returns a simplified array structure for each snapshot containing
     * only the essential fields.
     *
     * @param  AncestryType|string                                             $type The hierarchy type
     * @return array<int, array{ancestor_id: mixed, depth: int, type: string}> Array of snapshot data
     */
    public function getAncestrySnapshotsArray(AncestryType|string $type): array
    {
        /** @var array<int, array{ancestor_id: mixed, depth: int, type: string}> */
        return $this->getAncestrySnapshots($type)
            ->map(fn (AncestorSnapshot $snapshot): array => [
                'ancestor_id' => $snapshot->ancestor_id,
                'depth' => $snapshot->depth,
                'type' => $snapshot->type,
            ])
            ->toArray();
    }
}
