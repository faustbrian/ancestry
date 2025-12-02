<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Database\AncestorSnapshot;
use Cline\Ancestry\Facades\Ancestry;
use Tests\Fixtures\Order;

describe('AncestorSnapshot Model', function (): void {
    describe('attributes', function (): void {
        test('has correct fillable attributes', function (): void {
            $snapshot = new AncestorSnapshot();

            expect($snapshot->getFillable())->toBe([
                'context_type',
                'context_id',
                'type',
                'depth',
                'ancestor_id',
            ]);
        });

        test('casts depth to integer', function (): void {
            $user = user();
            Ancestry::addToAncestry($user, 'seller');
            $shipment = order();
            $shipment->snapshotAncestry($user, 'seller');

            $snapshot = $shipment->ancestrySnapshots()->first();

            expect($snapshot->depth)->toBeInt();
        });
    });

    describe('context relation', function (): void {
        test('returns the context model', function (): void {
            $user = user();
            Ancestry::addToAncestry($user, 'seller');
            $shipment = order(['reference' => 'TEST-001']);
            $shipment->snapshotAncestry($user, 'seller');

            $snapshot = AncestorSnapshot::query()->first();

            expect($snapshot->context)->toBeInstanceOf(Order::class);
            expect($snapshot->context->id)->toBe($shipment->id);
        });
    });

    describe('scopes', function (): void {
        beforeEach(function (): void {
            // Create two shipments with different hierarchies
            $seller1 = user();
            $seller2 = user();
            $reseller = user();

            Ancestry::addToAncestry($seller1, 'seller');
            Ancestry::addToAncestry($seller2, 'seller');
            Ancestry::addToAncestry($reseller, 'reseller');

            $this->shipment1 = order(['reference' => 'SHIP-001']);
            $this->shipment2 = order(['reference' => 'SHIP-002']);

            $this->shipment1->snapshotAncestry($seller1, 'seller');
            $this->shipment1->snapshotAncestry($reseller, 'reseller');

            $this->shipment2->snapshotAncestry($seller2, 'seller');
        });

        test('scopeForContext filters by context model', function (): void {
            $snapshots = AncestorSnapshot::query()
                ->forContext($this->shipment1)
                ->get();

            expect($snapshots)->toHaveCount(2); // seller + reseller
            expect($snapshots->every(fn ($s): bool => $s->context_id === $this->shipment1->id))->toBeTrue();
        });

        test('scopeOfType filters by hierarchy type', function (): void {
            $sellerSnapshots = AncestorSnapshot::query()
                ->ofType('seller')
                ->get();

            expect($sellerSnapshots)->toHaveCount(2); // one per shipment
            expect($sellerSnapshots->every(fn ($s): bool => $s->type === 'seller'))->toBeTrue();
        });

        test('scopeOrderedByDepth orders by depth ascending', function (): void {
            [$grandparent, $parent, $child] = createAncestorChain(3);
            $shipment = order();
            $shipment->snapshotAncestry($child, 'seller');

            $snapshots = AncestorSnapshot::query()
                ->forContext($shipment)
                ->ofType('seller')
                ->orderedByDepth()
                ->get();

            expect($snapshots[0]->depth)->toBe(0);
            expect($snapshots[1]->depth)->toBe(1);
            expect($snapshots[2]->depth)->toBe(2);
        });

        test('scopes can be chained', function (): void {
            $snapshots = AncestorSnapshot::query()
                ->forContext($this->shipment1)
                ->ofType('seller')
                ->orderedByDepth()
                ->get();

            expect($snapshots)->toHaveCount(1);
            expect($snapshots[0]->type)->toBe('seller');
        });
    });

    describe('table configuration', function (): void {
        test('uses configured table name', function (): void {
            $snapshot = new AncestorSnapshot();

            expect($snapshot->getTable())->toBe(
                config('ancestry.snapshots.table_name', 'hierarchy_snapshots'),
            );
        });
    });
});
