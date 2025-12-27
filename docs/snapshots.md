---
title: Ancestor Snapshots
description: Capture point-in-time hierarchy states for audit trails and historical reporting with Ancestry.
---

Snapshots capture the full hierarchy chain at a specific point in time, preserving historical relationships even when hierarchies change. This is essential for audit trails, commission calculations, or any scenario where you need to know what the hierarchy looked like at a specific moment.

## Use Cases

- **Commission calculations**: Capture the seller/reseller hierarchy when a shipment is created to ensure commissions are paid to the correct parties, even if the hierarchy changes later
- **Audit trails**: Record the organizational structure at the time of important business events
- **Historical reporting**: Generate reports showing who was responsible for what at any point in time
- **Compliance**: Maintain records of approval chains and authorization hierarchies

## Setup

### Add the Trait

Add the `HasAncestrySnapshots` trait to any model that needs to store hierarchy snapshots:

```php
<?php

namespace App\Models;

use Cline\Ancestry\Concerns\HasAncestrySnapshots;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasAncestrySnapshots;
}
```

### Publish Migration

If you haven't already, publish and run the snapshots migration:

```bash
php artisan vendor:publish --tag=ancestry-migrations
php artisan migrate
```

## Basic Usage

### Creating a Snapshot

Capture the current hierarchy state for a node:

```php
use App\Models\Shipment;
use App\Models\User;

$shipment = Shipment::create(['reference' => 'SHIP-001']);
$customer = User::find($customerId);

// Get the customer's assigned seller
$seller = $customer->assignedSeller;

// Snapshot the seller's hierarchy chain at this moment
$shipment->snapshotAncestry($seller, 'seller');
```

### Understanding Snapshot Depth

Snapshots store the full ancestor chain with depth levels:

```
CEO (depth 2)
└── VP Sales (depth 1)
    └── Sales Rep (depth 0) ← The node you snapshot
```

When you snapshot the Sales Rep, you get:
- Depth 0: Sales Rep (the direct node)
- Depth 1: VP Sales (parent)
- Depth 2: CEO (grandparent/root)

### Retrieving Snapshots

```php
// Get all snapshots for a hierarchy type
$snapshots = $shipment->getAncestrySnapshots('seller');

// Iterate through the hierarchy chain
foreach ($snapshots as $snapshot) {
    echo "Depth {$snapshot->depth}: User ID {$snapshot->ancestor_id}";
}

// Get the direct node (depth 0)
$directSeller = $shipment->getDirectAncestrySnapshot('seller');

// Get snapshot at specific depth
$parentSeller = $shipment->getAncestrySnapshotAtDepth('seller', 1);
```

### Checking for Snapshots

```php
if ($shipment->hasAncestrySnapshots('seller')) {
    // Process commission payments
}
```

### Clearing Snapshots

```php
// Clear snapshots for a specific type
$shipment->clearAncestrySnapshots('seller');
```

## Multiple Ancestor Types

You can snapshot different hierarchy types independently:

```php
// Snapshot both seller and reseller hierarchies
$shipment->snapshotAncestry($seller, 'seller');
$shipment->snapshotAncestry($reseller, 'reseller');

// Retrieve them separately
$sellerChain = $shipment->getAncestrySnapshots('seller');
$resellerChain = $shipment->getAncestrySnapshots('reseller');
```

## Snapshot Preservation

Snapshots are **point-in-time records**. They are NOT updated when the underlying hierarchy changes:

```php
// Create hierarchy: CEO -> VP -> Manager
$manager->addToAncestry('seller', $vp);

// Snapshot current state
$shipment->snapshotAncestry($manager, 'seller');

// Later, Manager moves to different VP
$manager->moveToAncestryParent($differentVp, 'seller');

// Original snapshot still shows old hierarchy!
$snapshots = $shipment->getAncestrySnapshots('seller');
// Still references original VP, not $differentVp
```

This is intentional - snapshots capture history, not current state.

## Re-snapshotting

Calling `snapshotAncestry()` again replaces existing snapshots for that type:

```php
// Initial snapshot
$shipment->snapshotAncestry($seller1, 'seller');

// Replace with new snapshot
$shipment->snapshotAncestry($seller2, 'seller');

// Only seller2's hierarchy is stored now
```

## Eager Loading

You can eager load snapshots to avoid N+1 queries:

```php
$shipments = Shipment::with('ancestrySnapshots')
    ->where('status', 'completed')
    ->get();

foreach ($shipments as $shipment) {
    // No additional queries
    $sellerSnapshots = $shipment->ancestrySnapshots
        ->where('type', 'seller')
        ->sortBy('depth');
}
```

## Querying Snapshots Directly

The `AncestorSnapshot` model provides useful scopes:

```php
use Cline\Ancestry\Database\AncestorSnapshot;

// Find all shipments where a specific user was in the seller hierarchy
$snapshots = AncestorSnapshot::query()
    ->where('ancestor_id', $userId)
    ->ofType('seller')
    ->with('context')
    ->get();

$shipmentIds = $snapshots->pluck('context_id');

// Find all snapshots for a specific context
$snapshots = AncestorSnapshot::query()
    ->forContext($shipment)
    ->ofType('seller')
    ->orderedByDepth()
    ->get();
```

## Configuration

Configure snapshot behavior in `config/ancestry.php`:

```php
'snapshots' => [
    // Enable/disable snapshot functionality
    'enabled' => env('ANCESTRY_SNAPSHOTS_ENABLED', true),

    // Custom model class
    'model' => \Cline\Ancestry\Database\AncestorSnapshot::class,

    // Table name
    'table_name' => env('ANCESTRY_SNAPSHOTS_TABLE', 'hierarchy_snapshots'),

    // Context morph type (morph, uuidMorph, ulidMorph)
    'context_morph_type' => env('ANCESTRY_SNAPSHOTS_CONTEXT_MORPH_TYPE', 'morph'),

    // Ancestor key type (id, ulid, uuid)
    'ancestor_key_type' => env('ANCESTRY_SNAPSHOTS_ANCESTOR_KEY_TYPE', 'ulid'),
],
```

## Example: Commission Calculation

Here's a real-world example of using snapshots for commission calculations:

```php
class ShipmentObserver
{
    public function created(Shipment $shipment): void
    {
        // Get the customer's assigned seller
        $seller = $shipment->user->assignedSeller;

        if ($seller) {
            // Capture the seller hierarchy at shipment creation
            $shipment->snapshotAncestry($seller, 'seller');
        }

        // Same for reseller
        $reseller = $shipment->user->assignedReseller;

        if ($reseller) {
            $shipment->snapshotAncestry($reseller, 'reseller');
        }
    }
}

class CommissionCalculator
{
    public function calculate(Shipment $shipment): array
    {
        $commissions = [];

        // Get the snapshotted seller hierarchy
        $sellerSnapshots = $shipment->getAncestrySnapshots('seller');

        foreach ($sellerSnapshots as $snapshot) {
            // Calculate commission based on depth
            $rate = $this->getCommissionRate($snapshot->depth);

            $commissions[] = [
                'user_id' => $snapshot->ancestor_id,
                'depth' => $snapshot->depth,
                'amount' => $shipment->total * $rate,
            ];
        }

        return $commissions;
    }

    private function getCommissionRate(int $depth): float
    {
        return match ($depth) {
            0 => 0.10,  // Direct seller: 10%
            1 => 0.05,  // Parent: 5%
            2 => 0.02,  // Grandparent: 2%
            default => 0.01,  // Higher levels: 1%
        };
    }
}
```

## Best Practices

1. **Snapshot early**: Capture hierarchies at the moment of the business event (order creation, not fulfillment)

2. **Don't over-snapshot**: Only snapshot when you need historical records

3. **Use appropriate types**: Use meaningful type names that match your business domain

4. **Consider cleanup**: Old snapshots can accumulate; consider archival strategies for historical data

5. **Index appropriately**: If querying snapshots frequently by `ancestor_id`, ensure proper indexes exist
