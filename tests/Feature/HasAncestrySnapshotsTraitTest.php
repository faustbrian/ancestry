<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Facades\Ancestry;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Tests\Fixtures\Order;

describe('HasAncestrySnapshots Trait', function (): void {
    describe('snapshotAncestry', function (): void {
        test('creates snapshots for full hierarchy chain', function (): void {
            // Create: CEO -> VP -> Manager (3-level hierarchy)
            [$ceo, $vp, $manager] = createAncestorChain(3);
            $shipment = order(['reference' => 'TEST-001']);

            // Snapshot the manager's seller hierarchy
            $shipment->snapshotAncestry($manager, 'seller');

            // Should have 3 snapshots (depths 0, 1, 2)
            $snapshots = $shipment->getAncestrySnapshots('seller');
            expect($snapshots)->toHaveCount(3);

            // Verify depth order (0 = direct, 1 = parent, 2 = grandparent)
            expect($snapshots[0]->depth)->toBe(0);
            expect($snapshots[0]->ancestor_id)->toEqual($manager->id);

            expect($snapshots[1]->depth)->toBe(1);
            expect($snapshots[1]->ancestor_id)->toEqual($vp->id);

            expect($snapshots[2]->depth)->toBe(2);
            expect($snapshots[2]->ancestor_id)->toEqual($ceo->id);
        });

        test('creates single snapshot for root node', function (): void {
            $root = user();
            Ancestry::addToAncestry($root, 'seller');
            $shipment = order();

            $shipment->snapshotAncestry($root, 'seller');

            $snapshots = $shipment->getAncestrySnapshots('seller');
            expect($snapshots)->toHaveCount(1);
            expect($snapshots[0]->depth)->toBe(0);
            expect($snapshots[0]->ancestor_id)->toEqual($root->id);
        });

        test('replaces existing snapshots when called again', function (): void {
            [$parent1, $child] = createAncestorChain(2);
            $shipment = order();

            // First snapshot
            $shipment->snapshotAncestry($child, 'seller');

            expect($shipment->getAncestrySnapshots('seller'))->toHaveCount(2);

            // Move child to different parent
            $parent2 = user();
            Ancestry::addToAncestry($parent2, 'seller');
            Ancestry::moveToParent($child, $parent2, 'seller');

            // Snapshot again - should replace
            $shipment->snapshotAncestry($child, 'seller');

            $snapshots = $shipment->getAncestrySnapshots('seller');
            expect($snapshots)->toHaveCount(2);
            expect($snapshots[1]->ancestor_id)->toEqual($parent2->id);
        });

        test('creates snapshots for different hierarchy types independently', function (): void {
            $seller = user();
            $reseller = user();
            Ancestry::addToAncestry($seller, 'seller');
            Ancestry::addToAncestry($reseller, 'reseller');

            $shipment = order();
            $shipment->snapshotAncestry($seller, 'seller');
            $shipment->snapshotAncestry($reseller, 'reseller');

            expect($shipment->getAncestrySnapshots('seller'))->toHaveCount(1);
            expect($shipment->getAncestrySnapshots('reseller'))->toHaveCount(1);
        });

        test('handles deep hierarchies', function (): void {
            // Create 5-level hierarchy
            $users = createAncestorChain(5);
            $shipment = order();

            $shipment->snapshotAncestry($users[4], 'seller');

            $snapshots = $shipment->getAncestrySnapshots('seller');
            expect($snapshots)->toHaveCount(5);

            // Verify all depths are correct
            foreach ($snapshots as $index => $snapshot) {
                expect($snapshot->depth)->toBe($index);
            }
        });
    });

    describe('getAncestrySnapshots', function (): void {
        test('returns empty collection when no snapshots exist', function (): void {
            $shipment = order();

            $snapshots = $shipment->getAncestrySnapshots('seller');

            expect($snapshots)->toBeEmpty();
        });

        test('returns snapshots ordered by depth', function (): void {
            [$grandparent, $parent, $child] = createAncestorChain(3);
            $shipment = order();
            $shipment->snapshotAncestry($child, 'seller');

            $snapshots = $shipment->getAncestrySnapshots('seller');

            expect($snapshots[0]->depth)->toBeLessThan($snapshots[1]->depth);
            expect($snapshots[1]->depth)->toBeLessThan($snapshots[2]->depth);
        });

        test('only returns snapshots for specified type', function (): void {
            $seller = user();
            $reseller = user();
            Ancestry::addToAncestry($seller, 'seller');
            Ancestry::addToAncestry($reseller, 'reseller');

            $shipment = order();
            $shipment->snapshotAncestry($seller, 'seller');
            $shipment->snapshotAncestry($reseller, 'reseller');

            $sellerSnapshots = $shipment->getAncestrySnapshots('seller');
            $resellerSnapshots = $shipment->getAncestrySnapshots('reseller');

            expect($sellerSnapshots)->toHaveCount(1);
            expect($resellerSnapshots)->toHaveCount(1);
            expect($sellerSnapshots[0]->ancestor_id)->toEqual($seller->id);
            expect($resellerSnapshots[0]->ancestor_id)->toEqual($reseller->id);
        });
    });

    describe('hasAncestrySnapshots', function (): void {
        test('returns false when no snapshots exist', function (): void {
            $shipment = order();

            expect($shipment->hasAncestrySnapshots('seller'))->toBeFalse();
        });

        test('returns true when snapshots exist', function (): void {
            $user = user();
            Ancestry::addToAncestry($user, 'seller');
            $shipment = order();
            $shipment->snapshotAncestry($user, 'seller');

            expect($shipment->hasAncestrySnapshots('seller'))->toBeTrue();
        });

        test('returns false for different type', function (): void {
            $user = user();
            Ancestry::addToAncestry($user, 'seller');
            $shipment = order();
            $shipment->snapshotAncestry($user, 'seller');

            expect($shipment->hasAncestrySnapshots('reseller'))->toBeFalse();
        });
    });

    describe('clearAncestrySnapshots', function (): void {
        test('removes all snapshots for a type', function (): void {
            [$parent, $child] = createAncestorChain(2);
            $shipment = order();
            $shipment->snapshotAncestry($child, 'seller');

            expect($shipment->hasAncestrySnapshots('seller'))->toBeTrue();

            $shipment->clearAncestrySnapshots('seller');

            expect($shipment->hasAncestrySnapshots('seller'))->toBeFalse();
            expect($shipment->getAncestrySnapshots('seller'))->toBeEmpty();
        });

        test('only clears specified type', function (): void {
            $seller = user();
            $reseller = user();
            Ancestry::addToAncestry($seller, 'seller');
            Ancestry::addToAncestry($reseller, 'reseller');

            $shipment = order();
            $shipment->snapshotAncestry($seller, 'seller');
            $shipment->snapshotAncestry($reseller, 'reseller');

            $shipment->clearAncestrySnapshots('seller');

            expect($shipment->hasAncestrySnapshots('seller'))->toBeFalse();
            expect($shipment->hasAncestrySnapshots('reseller'))->toBeTrue();
        });
    });

    describe('getAncestrySnapshotAtDepth', function (): void {
        test('returns snapshot at specific depth', function (): void {
            [$grandparent, $parent, $child] = createAncestorChain(3);
            $shipment = order();
            $shipment->snapshotAncestry($child, 'seller');

            $snapshot = $shipment->getAncestrySnapshotAtDepth('seller', 1);

            expect($snapshot)->not->toBeNull();
            expect($snapshot->ancestor_id)->toEqual($parent->id);
        });

        test('returns null for non-existent depth', function (): void {
            $user = user();
            Ancestry::addToAncestry($user, 'seller');
            $shipment = order();
            $shipment->snapshotAncestry($user, 'seller');

            $snapshot = $shipment->getAncestrySnapshotAtDepth('seller', 5);

            expect($snapshot)->toBeNull();
        });
    });

    describe('getDirectAncestrySnapshot', function (): void {
        test('returns depth 0 snapshot', function (): void {
            [$parent, $child] = createAncestorChain(2);
            $shipment = order();
            $shipment->snapshotAncestry($child, 'seller');

            $direct = $shipment->getDirectAncestrySnapshot('seller');

            expect($direct)->not->toBeNull();
            expect($direct->depth)->toBe(0);
            expect($direct->ancestor_id)->toEqual($child->id);
        });

        test('returns null when no snapshots exist', function (): void {
            $shipment = order();

            expect($shipment->getDirectAncestrySnapshot('seller'))->toBeNull();
        });
    });

    describe('ancestrySnapshots relation', function (): void {
        test('returns morphMany relation', function (): void {
            $shipment = order();

            expect($shipment->ancestrySnapshots())->toBeInstanceOf(
                MorphMany::class,
            );
        });

        test('can eager load snapshots', function (): void {
            $user = user();
            Ancestry::addToAncestry($user, 'seller');
            $shipment = order();
            $shipment->snapshotAncestry($user, 'seller');

            $loaded = Order::with('ancestrySnapshots')->find($shipment->id);

            expect($loaded->ancestrySnapshots)->toHaveCount(1);
        });
    });

    describe('snapshot preservation', function (): void {
        test('snapshots are preserved when hierarchy changes', function (): void {
            // Create hierarchy and snapshot
            [$originalParent, $child] = createAncestorChain(2);
            $shipment = order();
            $shipment->snapshotAncestry($child, 'seller');

            // Verify original snapshot
            $originalSnapshot = $shipment->getAncestrySnapshots('seller');
            expect($originalSnapshot[1]->ancestor_id)->toEqual($originalParent->id);

            // Move child to new parent
            $newParent = user();
            Ancestry::addToAncestry($newParent, 'seller');
            Ancestry::moveToParent($child, $newParent, 'seller');

            // Refresh shipment from DB and verify snapshot unchanged
            $shipment->refresh();
            $preservedSnapshot = $shipment->getAncestrySnapshots('seller');

            expect($preservedSnapshot[1]->ancestor_id)->toEqual($originalParent->id);
        });

        test('snapshots are preserved when ancestor is deleted from hierarchy', function (): void {
            [$grandparent, $parent, $child] = createAncestorChain(3);
            $shipment = order();
            $shipment->snapshotAncestry($child, 'seller');

            // Remove parent from hierarchy (orphans child)
            Ancestry::removeFromAncestry($parent, 'seller');

            // Snapshot should still reference the original parent
            $snapshots = $shipment->getAncestrySnapshots('seller');
            expect($snapshots[1]->ancestor_id)->toEqual($parent->id);
        });
    });
});
