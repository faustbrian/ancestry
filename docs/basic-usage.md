---
title: Basic Usage
description: Learn fundamental operations for managing hierarchies with Ancestry.
---

This guide covers the fundamental operations for managing hierarchies with Ancestry.

## Adding to a Ancestor

### As a Root Node

```php
use Cline\Ancestry\Facades\Ancestry;

$ceo = Seller::create(['name' => 'CEO']);
Ancestry::addToAncestry($ceo, 'seller');
```

### With a Parent

```php
$vp = Seller::create(['name' => 'VP']);
Ancestry::addToAncestry($vp, 'seller', $ceo);
```

## Querying Relationships

### Get Ancestors

```php
// Get all ancestors (ordered from nearest to farthest)
$ancestors = Ancestry::getAncestors($seller, 'seller');

// Include self in results
$ancestorsWithSelf = Ancestry::getAncestors($seller, 'seller', includeSelf: true);

// Limit depth
$nearestTwo = Ancestry::getAncestors($seller, 'seller', maxDepth: 2);
```

### Get Descendants

```php
// Get all descendants (ordered from nearest to farthest)
$descendants = Ancestry::getDescendants($ceo, 'seller');

// Include self in results
$descendantsWithSelf = Ancestry::getDescendants($ceo, 'seller', includeSelf: true);

// Limit depth (direct children only)
$directReports = Ancestry::getDescendants($ceo, 'seller', maxDepth: 1);
```

### Get Direct Relationships

```php
// Get direct parent
$parent = Ancestry::getDirectParent($seller, 'seller');

// Get direct children
$children = Ancestry::getDirectChildren($manager, 'seller');
```

## Checking Relationships

```php
// Check if one model is ancestor of another
$isAncestor = Ancestry::isAncestorOf($ceo, $seller, 'seller'); // true

// Check if one model is descendant of another
$isDescendant = Ancestry::isDescendantOf($seller, $ceo, 'seller'); // true

// Check if model is in a hierarchy
$isInAncestry = Ancestry::isInAncestry($seller, 'seller'); // true

// Check if model is a root (no parent)
$isRoot = Ancestry::isRoot($ceo, 'seller'); // true

// Check if model is a leaf (no children)
$isLeaf = Ancestry::isLeaf($seller, 'seller'); // true
```

## Getting Depth and Position

```php
// Get depth in hierarchy (0 = root)
$depth = Ancestry::getDepth($seller, 'seller');

// Get root(s) of the hierarchy
$roots = Ancestry::getRoots($seller, 'seller');

// Get siblings (same parent)
$siblings = Ancestry::getSiblings($seller, 'seller');

// Get path from root to model
$path = Ancestry::getPath($seller, 'seller');
// Returns: [CEO, VP, Manager, Seller]
```

## Building Trees

```php
// Build a tree structure starting from a node
$tree = Ancestry::buildTree($ceo, 'seller');

// Returns:
// [
//     'model' => $ceo,
//     'children' => [
//         [
//             'model' => $vp,
//             'children' => [
//                 [
//                     'model' => $manager,
//                     'children' => [
//                         [
//                             'model' => $seller,
//                             'children' => []
//                         ]
//                     ]
//                 ]
//             ]
//         ]
//     ]
// ]
```

## Getting All Roots

```php
// Get all root nodes for a hierarchy type
$allRoots = Ancestry::getRootNodes('seller');
```

## Modifying Hierarchies

### Detach from Parent

```php
// Detach from parent (become a root)
Ancestry::detachFromParent($manager, 'seller');
```

### Attach to New Parent

```php
// Attach an existing node to a parent
Ancestry::attachToParent($manager, $newVp, 'seller');
```

### Move to Different Parent

```php
// Move node (with all descendants) to new parent
Ancestry::moveToParent($manager, $newVp, 'seller');

// Move to become root
Ancestry::moveToParent($manager, null, 'seller');
```

### Remove from Ancestor

```php
// Remove completely from hierarchy
Ancestry::removeFromAncestry($seller, 'seller');
```

## Using the Trait

All operations are also available directly on models using the `HasAncestry` trait:

```php
$seller->addToAncestry('seller', $manager);
$seller->getAncestryAncestors('seller');
$seller->getAncestryDescendants('seller');
$seller->getAncestryParent('seller');
$seller->getAncestryChildren('seller');
$seller->isAncestryAncestorOf($other, 'seller');
$seller->isAncestryDescendantOf($other, 'seller');
$seller->getAncestryDepth('seller');
$seller->getAncestryRoots('seller');
$seller->getAncestryPath('seller');
$seller->buildAncestryTree('seller');
$seller->isInAncestry('seller');
$seller->isAncestryRoot('seller');
$seller->isAncestryLeaf('seller');
$seller->getAncestrySiblings('seller');
$seller->detachFromAncestryParent('seller');
$seller->attachToAncestryParent($parent, 'seller');
$seller->moveToAncestryParent($newParent, 'seller');
$seller->removeFromAncestry('seller');
```
