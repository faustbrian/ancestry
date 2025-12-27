---
title: Multiple Ancestor Types
description: Manage multiple independent hierarchical relationships simultaneously with Ancestry.
---

Ancestry supports multiple hierarchy types, allowing a single model to participate in different hierarchical relationships simultaneously.

## Why Multiple Types?

Consider a company where:
- Sellers have a sales hierarchy (CEO → VP → Manager → Seller)
- Sellers can also be resellers with their own hierarchy
- Organizations have a separate corporate structure

One `User` or `Seller` model might exist in all three hierarchies with different positions in each.

## Using Multiple Types

### Adding to Different Hierarchies

```php
use Cline\Ancestry\Facades\Ancestry;

$user = User::find(1);

// Add to seller hierarchy
Ancestry::addToAncestry($user, 'seller', $salesManager);

// Add to reseller hierarchy
Ancestry::addToAncestry($user, 'reseller', $resellerManager);

// Add to organization hierarchy
Ancestry::addToAncestry($user, 'organization', $department);
```

### Querying Different Hierarchies

```php
// Get ancestors in seller hierarchy
$salesAncestors = Ancestry::getAncestors($user, 'seller');

// Get ancestors in reseller hierarchy
$resellerAncestors = Ancestry::getAncestors($user, 'reseller');

// Check position in each hierarchy
$sellerDepth = Ancestry::getDepth($user, 'seller');
$resellerDepth = Ancestry::getDepth($user, 'reseller');
```

### Hierarchies Are Isolated

Each hierarchy type is completely independent:

```php
// Different parents in each hierarchy
$sellerParent = Ancestry::getDirectParent($user, 'seller');
$resellerParent = Ancestry::getDirectParent($user, 'reseller');

// These are typically different models
$sellerParent !== $resellerParent; // true (usually)
```

## Type-Safe Hierarchies with Enums

For type safety, use a backed string enum:

```php
<?php

namespace App\Enums;

use Cline\Ancestry\Contracts\AncestryType;

enum AncestryType: string implements AncestryType
{
    case Seller = 'seller';
    case Reseller = 'reseller';
    case Organization = 'organization';

    public function value(): string
    {
        return $this->value;
    }
}
```

Then use the enum in your code:

```php
use App\Enums\AncestryType;

// IDE autocompletion and type safety!
Ancestry::addToAncestry($user, AncestryType::Seller, $manager);
Ancestry::getAncestors($user, AncestryType::Seller);
Ancestry::isDescendantOf($user, $ceo, AncestryType::Seller);
```

Configure the enum in `config/ancestry.php`:

```php
'type_enum' => \App\Enums\AncestryType::class,
```

## Using the Fluent API with Types

### For Model Conductor

```php
// Switch between types easily
$sellerConductor = Ancestry::for($user)->type(AncestryType::Seller);
$resellerConductor = Ancestry::for($user)->type(AncestryType::Reseller);

$sellerAncestors = $sellerConductor->ancestors();
$resellerAncestors = $resellerConductor->ancestors();
```

### Type Conductor

```php
// Work with all nodes in a specific hierarchy
$sellerRoots = Ancestry::ofType(AncestryType::Seller)->roots();
$resellerRoots = Ancestry::ofType(AncestryType::Reseller)->roots();
```

## Common Patterns

### Different Hierarchies, Same Model

```php
class User extends Model
{
    use HasAncestry;

    public function getSellerAncestors(): Collection
    {
        return $this->getAncestryAncestors(AncestryType::Seller);
    }

    public function getResellerAncestors(): Collection
    {
        return $this->getAncestryAncestors(AncestryType::Reseller);
    }

    public function getOrganizationPath(): Collection
    {
        return $this->getAncestryPath(AncestryType::Organization);
    }
}
```

### Checking Multiple Hierarchies

```php
// Is user in ANY hierarchy?
$inAnyAncestor = Ancestry::isInAncestry($user, AncestryType::Seller)
    || Ancestry::isInAncestry($user, AncestryType::Reseller)
    || Ancestry::isInAncestry($user, AncestryType::Organization);

// Get all hierarchies user is in
$hierarchies = collect([AncestryType::Seller, AncestryType::Reseller, AncestryType::Organization])
    ->filter(fn ($type) => Ancestry::isInAncestry($user, $type));
```

### Moving Between Hierarchies

Moving within one hierarchy doesn't affect others:

```php
// Move in seller hierarchy
Ancestry::moveToParent($user, $newManager, AncestryType::Seller);

// Reseller hierarchy is unchanged
$resellerParent = Ancestry::getDirectParent($user, AncestryType::Reseller);
// Still the same as before
```

### Removing from Specific Ancestor

```php
// Remove from seller hierarchy only
Ancestry::removeFromAncestry($user, AncestryType::Seller);

// User is still in other hierarchies
Ancestry::isInAncestry($user, AncestryType::Reseller); // true
Ancestry::isInAncestry($user, AncestryType::Seller);   // false
```

## Database Storage

All hierarchy types are stored in the same table, differentiated by the `type` column:

```sql
SELECT * FROM hierarchies WHERE type = 'seller';
SELECT * FROM hierarchies WHERE type = 'reseller';
```

This allows efficient querying within a type while maintaining isolation between types.
