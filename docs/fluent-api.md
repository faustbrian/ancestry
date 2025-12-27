---
title: Fluent API
description: Master Ancestry's fluent, chainable API for managing hierarchies.
---

Ancestry provides a fluent, chainable API for managing hierarchies. This guide covers both conductor types.

## For Model Conductor

The `for()` conductor starts operations on a specific model:

```php
use Cline\Ancestry\Facades\Ancestry;

Ancestry::for($model)->type('seller')->...
```

### Setting the Type

Always set the hierarchy type before performing operations:

```php
$conductor = Ancestry::for($seller)->type('seller');
```

### Adding to Ancestor

```php
// Add as root
Ancestry::for($seller)->type('seller')->add();

// Add with parent
Ancestry::for($seller)->type('seller')->add($manager);
```

### Attaching and Detaching

```php
// Attach to parent
Ancestry::for($seller)->type('seller')->attachTo($manager);

// Detach from parent (become root)
Ancestry::for($seller)->type('seller')->detach();
```

### Moving

```php
// Move to new parent
Ancestry::for($seller)->type('seller')->moveTo($newManager);

// Move to become root
Ancestry::for($seller)->type('seller')->moveTo(null);
```

### Removing

```php
Ancestry::for($seller)->type('seller')->remove();
```

### Querying Relationships

```php
// Get ancestors
$ancestors = Ancestry::for($seller)->type('seller')->ancestors();
$ancestorsWithSelf = Ancestry::for($seller)->type('seller')->ancestors(includeSelf: true);
$nearestTwo = Ancestry::for($seller)->type('seller')->ancestors(maxDepth: 2);

// Get descendants
$descendants = Ancestry::for($ceo)->type('seller')->descendants();
$children = Ancestry::for($ceo)->type('seller')->descendants(maxDepth: 1);

// Get parent
$parent = Ancestry::for($seller)->type('seller')->parent();

// Get children
$children = Ancestry::for($manager)->type('seller')->children();

// Get siblings
$siblings = Ancestry::for($seller)->type('seller')->siblings();
$siblingsWithSelf = Ancestry::for($seller)->type('seller')->siblings(includeSelf: true);
```

### Checking Relationships

```php
// Check ancestry
$isAncestor = Ancestry::for($ceo)->type('seller')->isAncestorOf($seller);
$isDescendant = Ancestry::for($seller)->type('seller')->isDescendantOf($ceo);

// Check position
$isInAncestry = Ancestry::for($seller)->type('seller')->isInAncestry();
$isRoot = Ancestry::for($ceo)->type('seller')->isRoot();
$isLeaf = Ancestry::for($seller)->type('seller')->isLeaf();
```

### Getting Position Information

```php
// Get depth
$depth = Ancestry::for($seller)->type('seller')->depth();

// Get roots
$roots = Ancestry::for($seller)->type('seller')->roots();

// Get path from root
$path = Ancestry::for($seller)->type('seller')->path();

// Build tree
$tree = Ancestry::for($ceo)->type('seller')->tree();
```

### Chaining Operations

The conductor returns itself for modification operations, allowing chaining:

```php
Ancestry::for($seller)
    ->type('seller')
    ->add($manager)
    ->detach()
    ->attachTo($newManager);
```

## Type Conductor

The `ofType()` conductor starts operations on a hierarchy type:

```php
use Cline\Ancestry\Facades\Ancestry;

Ancestry::ofType('seller')->...
```

### Getting Root Nodes

```php
$roots = Ancestry::ofType('seller')->roots();
```

### Adding Models

```php
// Add as root
Ancestry::ofType('seller')->add($seller);

// Add with parent
Ancestry::ofType('seller')->add($seller, $manager);
```

### Getting Model Conductor

You can transition to a model conductor with the type already set:

```php
$conductor = Ancestry::ofType('seller')->for($seller);

// Now perform operations without setting type again
$ancestors = $conductor->ancestors();
$conductor->moveTo($newManager);
```

## Combining Approaches

You can use both conductors together for expressive code:

```php
// Get all roots in the seller hierarchy
$roots = Ancestry::ofType('seller')->roots();

// For each root, get all descendants
foreach ($roots as $root) {
    $descendants = Ancestry::for($root)
        ->type('seller')
        ->descendants();

    // Or using ofType
    $descendants = Ancestry::ofType('seller')
        ->for($root)
        ->descendants();
}
```

## Error Handling

The conductor throws a `RuntimeException` if you try to perform operations without setting the type:

```php
// This will throw an exception
Ancestry::for($seller)->ancestors(); // RuntimeException: Ancestor type must be set
```

Always set the type first:

```php
Ancestry::for($seller)->type('seller')->ancestors(); // Works!
```
