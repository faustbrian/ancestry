## Table of Contents

1. [Getting Started](#doc-docs-readme)
2. [Basic Usage](#doc-docs-basic-usage)
3. [Fluent API](#doc-docs-fluent-api)
4. [Configuration](#doc-docs-configuration)
5. [Multiple Hierarchy Types](#doc-docs-multiple-types)
6. [Custom Key Mapping](#doc-docs-custom-key-mapping)
7. [Events](#doc-docs-events)
8. [Snapshots](#doc-docs-snapshots)
<a id="doc-docs-readme"></a>

Ancestry provides closure table hierarchies for Eloquent models with O(1) ancestor/descendant queries. This guide will help you get started quickly.

## Installation

```bash
composer require cline/ancestry
```

## Publish Configuration

```bash
php artisan vendor:publish --tag=ancestry-config
```

## Run Migrations

```bash
php artisan migrate
```

## Basic Setup

Add the `HasAncestry` trait to any model that needs hierarchical relationships:

```php
<?php

namespace App\Models;

use Cline\Ancestry\Concerns\HasAncestry;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasAncestry;
}
```

## Quick Example

```php
use App\Models\Seller;
use Cline\Ancestry\Facades\Ancestry;

// Create a hierarchy
$ceo = Seller::create(['name' => 'CEO']);
$vp = Seller::create(['name' => 'VP of Sales']);
$manager = Seller::create(['name' => 'Regional Manager']);
$seller = Seller::create(['name' => 'Sales Rep']);

// Build the hierarchy
Ancestry::addToAncestry($ceo, 'seller');
Ancestry::addToAncestry($vp, 'seller', $ceo);
Ancestry::addToAncestry($manager, 'seller', $vp);
Ancestry::addToAncestry($seller, 'seller', $manager);

// Query the hierarchy
$ancestors = Ancestry::getAncestors($seller, 'seller');
// Returns: [Regional Manager, VP of Sales, CEO]

$descendants = Ancestry::getDescendants($ceo, 'seller');
// Returns: [VP of Sales, Regional Manager, Sales Rep]

$depth = Ancestry::getDepth($seller, 'seller');
// Returns: 3
```

## Using the Fluent API

```php
// Using the for() conductor
Ancestry::for($seller)
    ->type('seller')
    ->ancestors();

// Using the ofType() conductor
Ancestry::ofType('seller')
    ->roots();
```

## Using the Trait Methods

```php
// Using trait methods directly on the model
$seller->addToAncestry('seller', $manager);
$seller->getAncestryAncestors('seller');
$seller->isAncestryDescendantOf($ceo, 'seller');
```

## Next Steps

- [Basic Usage](#doc-docs-basic-usage) - Learn the core operations
- [Fluent API](#doc-docs-fluent-api) - Master the chainable interface
- [Configuration](#doc-docs-configuration) - Customize Ancestry for your needs
- [Multiple Ancestor Types](#doc-docs-multiple-types) - Manage different hierarchies

<a id="doc-docs-basic-usage"></a>

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

<a id="doc-docs-fluent-api"></a>

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

<a id="doc-docs-configuration"></a>

Ancestry is highly configurable. This guide covers all available options.

## Publishing Configuration

```bash
php artisan vendor:publish --tag=ancestry-config
```

This creates `config/ancestry.php`.

## Primary Key Type

Control the primary key type for the hierarchies table:

```php
'primary_key_type' => env('ANCESTRY_PRIMARY_KEY_TYPE', 'id'),
```

Supported values:
- `'id'` - Auto-incrementing integers (default)
- `'ulid'` - ULIDs for sortable, time-ordered identifiers
- `'uuid'` - UUIDs for globally unique identifiers

## Morph Types

Configure polymorphic relationship column types separately for ancestors and descendants:

```php
'ancestor_morph_type' => env('ANCESTRY_ANCESTOR_MORPH_TYPE', 'morph'),
'descendant_morph_type' => env('ANCESTRY_DESCENDANT_MORPH_TYPE', 'morph'),
```

Supported values:
- `'morph'` - Standard morphs (default)
- `'uuidMorph'` - UUID-based morphs
- `'ulidMorph'` - ULID-based morphs

## Maximum Depth

Limit hierarchy depth to prevent abuse:

```php
'max_depth' => env('ANCESTRY_MAX_DEPTH', 10),
```

Set to `null` for unlimited depth (not recommended for production).

## Custom Ancestor Model

Use a custom Ancestor model:

```php
'models' => [
    'hierarchy' => \App\Models\CustomAncestor::class,
],
```

Your custom model must extend `Cline\Ancestry\Database\Ancestor`.

## Table Name

Customize the table name:

```php
'table_name' => env('ANCESTRY_TABLE', 'hierarchies'),
```

## Polymorphic Key Mapping

Map models to their primary key columns for mixed key types:

```php
'morphKeyMap' => [
    \App\Models\User::class => 'id',
    \App\Models\Seller::class => 'ulid',
    \App\Models\Organization::class => 'uuid',
],
```

### Enforced Key Mapping

Enable strict enforcement to throw exceptions for unmapped models:

```php
'enforceMorphKeyMap' => [
    \App\Models\User::class => 'id',
    \App\Models\Seller::class => 'ulid',
],
```

**Note:** Only configure either `morphKeyMap` or `enforceMorphKeyMap`, not both.

## Events

Control event dispatching:

```php
'events' => [
    'enabled' => env('ANCESTRY_EVENTS_ENABLED', true),
],
```

Events dispatched:
- `NodeAttached` - When a node is attached to a parent
- `NodeDetached` - When a node is detached from its parent
- `NodeMoved` - When a node is moved to a new parent
- `NodeRemoved` - When a node is completely removed

## Caching

Configure hierarchy query caching:

```php
'cache' => [
    'enabled' => env('ANCESTRY_CACHE_ENABLED', false),
    'store' => env('ANCESTRY_CACHE_STORE'),
    'prefix' => env('ANCESTRY_CACHE_PREFIX', 'ancestry'),
    'ttl' => env('ANCESTRY_CACHE_TTL', 3600),
],
```

## Strict Mode

Enable strict mode for development:

```php
'strict' => env('ANCESTRY_STRICT', true),
```

Strict mode enforces:
- Detailed error messages for circular references
- Clear exceptions for depth violations
- Type mismatch detection

## Database Connection

Use a separate database connection:

```php
'connection' => env('ANCESTRY_CONNECTION'),
```

## Ancestor Types

Define available hierarchy types (optional):

```php
'types' => [
    'seller',
    'reseller',
    'organization',
],
```

Or use a backed enum:

```php
'type_enum' => \App\Enums\AncestryType::class,
```

## Environment Variables

All configuration can be set via environment variables:

```env
ANCESTRY_PRIMARY_KEY_TYPE=ulid
ANCESTRY_ANCESTOR_MORPH_TYPE=ulid
ANCESTRY_DESCENDANT_MORPH_TYPE=ulid
ANCESTRY_MAX_DEPTH=15
ANCESTRY_TABLE=custom_hierarchies
ANCESTRY_EVENTS_ENABLED=true
ANCESTRY_CACHE_ENABLED=true
ANCESTRY_CACHE_STORE=redis
ANCESTRY_CACHE_PREFIX=hierarchy
ANCESTRY_CACHE_TTL=7200
ANCESTRY_STRICT=true
ANCESTRY_CONNECTION=hierarchy_db
```

## Example Configuration

Here's a complete example for a production setup:

```php
<?php

return [
    'primary_key_type' => 'ulid',
    'ancestor_morph_type' => 'ulidMorph',
    'descendant_morph_type' => 'ulidMorph',
    'max_depth' => 10,

    'models' => [
        'hierarchy' => \Cline\Ancestry\Database\Ancestor::class,
    ],

    'table_name' => 'hierarchies',

    'enforceMorphKeyMap' => [
        \App\Models\User::class => 'ulid',
        \App\Models\Seller::class => 'ulid',
        \App\Models\Organization::class => 'ulid',
    ],

    'events' => [
        'enabled' => true,
    ],

    'cache' => [
        'enabled' => true,
        'store' => 'redis',
        'prefix' => 'ancestry',
        'ttl' => 3600,
    ],

    'strict' => true,
    'connection' => null,
];
```

<a id="doc-docs-multiple-types"></a>

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

<a id="doc-docs-custom-key-mapping"></a>

Ancestry supports custom polymorphic key mappings, allowing you to use any column as the identifier in hierarchy relationships instead of the default primary key.

## Why Custom Key Mapping?

By default, Ancestry uses each model's primary key (`id`) to store ancestor/descendant relationships. However, you may need to:

- Use UUIDs or ULIDs stored in a different column
- Reference models by a unique business identifier (e.g., `email`, `slug`)
- Support mixed key types across different models

## Configuration

### morphKeyMap (Optional Mapping)

Define key mappings in `config/ancestry.php`:

```php
'morphKeyMap' => [
    \App\Models\User::class => 'uuid',
    \App\Models\Seller::class => 'ulid',
    \App\Models\Organization::class => 'external_id',
],
```

Models not in the map fall back to their default primary key.

### enforceMorphKeyMap (Strict Mapping)

For stricter control, use `enforceMorphKeyMap` to require all models be explicitly mapped:

```php
'enforceMorphKeyMap' => [
    \App\Models\User::class => 'uuid',
    \App\Models\Seller::class => 'ulid',
],
```

Using an unmapped model throws `MorphKeyViolationException`:

```php
use App\Models\Post;

// Throws MorphKeyViolationException: Model [App\Models\Post] is not mapped
Ancestry::addToAncestry($post, 'category');
```

**Note:** Configure either `morphKeyMap` or `enforceMorphKeyMap`, not both.

## Programmatic Configuration

You can also configure mappings at runtime via `ModelRegistry`:

```php
use Cline\Ancestry\Database\ModelRegistry;

$registry = app(ModelRegistry::class);

// Optional mapping
$registry->morphKeyMap([
    User::class => 'email',
]);

// Strict mapping
$registry->enforceMorphKeyMap([
    User::class => 'email',
    Seller::class => 'ulid',
]);

// Enable strict mode separately
$registry->morphKeyMap([User::class => 'email']);
$registry->requireKeyMap();
```

## Example: Using Email as Key

```php
// config/ancestry.php
'morphKeyMap' => [
    \App\Models\User::class => 'email',
],
```

```php
$manager = User::create(['name' => 'Manager', 'email' => 'manager@company.com']);
$seller = User::create(['name' => 'Seller', 'email' => 'seller@company.com']);

Ancestry::addToAncestry($manager, 'sales');
Ancestry::addToAncestry($seller, 'sales', $manager);

// Database stores 'manager@company.com' and 'seller@company.com' as the IDs
// Queries automatically use the email column
$ancestors = Ancestry::getAncestors($seller, 'sales');
// Returns the manager User model
```

## Example: Mixed Key Types

Different models can use different key columns:

```php
'morphKeyMap' => [
    \App\Models\User::class => 'uuid',      // Users identified by UUID
    \App\Models\Team::class => 'slug',      // Teams identified by slug
    \App\Models\Department::class => 'id',  // Departments use standard ID
],
```

```php
$team = Team::create(['name' => 'Sales', 'slug' => 'sales-team']);
$user = User::create(['name' => 'John', 'uuid' => 'abc-123']);

Ancestry::addToAncestry($team, 'organization');
Ancestry::addToAncestry($user, 'organization', $team);

// team's slug 'sales-team' stored as ancestor_id
// user's uuid 'abc-123' stored as descendant_id
```

## Database Considerations

### Column Types

When using custom keys, ensure your morph type configuration matches:

| Key Type | Recommended Morph Type |
|----------|----------------------|
| Integer IDs | `morph` or `numericMorph` |
| UUIDs | `uuidMorph` |
| ULIDs | `ulidMorph` |
| Strings (email, slug) | `morph` (varchar) |

```php
// config/ancestry.php
'ancestor_morph_type' => 'morph',    // varchar - flexible for any string
'descendant_morph_type' => 'morph',
```

### Indexing

Add indexes on your custom key columns for optimal query performance:

```php
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->index('uuid');
});
```

## Testing with Custom Keys

Reset the registry between tests to prevent state leakage:

```php
use Cline\Ancestry\Database\ModelRegistry;

beforeEach(function () {
    app(ModelRegistry::class)->reset();
});

test('uses custom key mapping', function () {
    app(ModelRegistry::class)->morphKeyMap([
        User::class => 'email',
    ]);

    $parent = User::create(['email' => 'parent@test.com']);
    $child = User::create(['email' => 'child@test.com']);

    Ancestry::addToAncestry($parent, 'test');
    Ancestry::addToAncestry($child, 'test', $parent);

    expect(Ancestry::getAncestors($child, 'test'))->toHaveCount(1);
});
```

## Error Handling

```php
use Cline\Ancestry\Exceptions\MorphKeyViolationException;

try {
    Ancestry::addToAncestry($unmappedModel, 'hierarchy');
} catch (MorphKeyViolationException $e) {
    // Handle unmapped model when enforcement is enabled
    Log::warning($e->getMessage());
}
```

<a id="doc-docs-events"></a>

Ancestry dispatches events during hierarchy operations, enabling you to react to changes in your application.

## Available Events

### NodeAttached

Dispatched when a node is attached to a parent:

```php
use Cline\Ancestry\Events\NodeAttached;

class NodeAttachedListener
{
    public function handle(NodeAttached $event): void
    {
        $node = $event->node;           // The model being attached
        $parent = $event->parent;       // The parent model
        $type = $event->type;           // The hierarchy type (string)

        // Your logic here
        Log::info("Node {$node->id} attached to {$parent->id} in {$type} hierarchy");
    }
}
```

### NodeDetached

Dispatched when a node is detached from its parent:

```php
use Cline\Ancestry\Events\NodeDetached;

class NodeDetachedListener
{
    public function handle(NodeDetached $event): void
    {
        $node = $event->node;                   // The model being detached
        $previousParent = $event->previousParent; // The former parent
        $type = $event->type;                   // The hierarchy type
    }
}
```

### NodeMoved

Dispatched when a node is moved to a new parent:

```php
use Cline\Ancestry\Events\NodeMoved;

class NodeMovedListener
{
    public function handle(NodeMoved $event): void
    {
        $node = $event->node;                   // The model being moved
        $previousParent = $event->previousParent; // The former parent (may be null)
        $newParent = $event->newParent;         // The new parent (may be null if becoming root)
        $type = $event->type;                   // The hierarchy type
    }
}
```

### NodeRemoved

Dispatched when a node is completely removed from a hierarchy:

```php
use Cline\Ancestry\Events\NodeRemoved;

class NodeRemovedListener
{
    public function handle(NodeRemoved $event): void
    {
        $node = $event->node;   // The model being removed
        $type = $event->type;   // The hierarchy type
    }
}
```

## Registering Listeners

### In EventServiceProvider

```php
use Cline\Ancestry\Events\NodeAttached;
use Cline\Ancestry\Events\NodeDetached;
use Cline\Ancestry\Events\NodeMoved;
use Cline\Ancestry\Events\NodeRemoved;

protected $listen = [
    NodeAttached::class => [
        \App\Listeners\NodeAttachedListener::class,
    ],
    NodeDetached::class => [
        \App\Listeners\NodeDetachedListener::class,
    ],
    NodeMoved::class => [
        \App\Listeners\NodeMovedListener::class,
    ],
    NodeRemoved::class => [
        \App\Listeners\NodeRemovedListener::class,
    ],
];
```

### Using Closures

```php
use Illuminate\Support\Facades\Event;
use Cline\Ancestry\Events\NodeAttached;

Event::listen(NodeAttached::class, function (NodeAttached $event) {
    // Handle the event
});
```

## Common Use Cases

### Audit Logging

```php
class AncestorAuditListener
{
    public function handleAttach(NodeAttached $event): void
    {
        AuditLog::create([
            'action' => 'hierarchy_attach',
            'node_type' => $event->node->getMorphClass(),
            'node_id' => $event->node->getKey(),
            'parent_type' => $event->parent->getMorphClass(),
            'parent_id' => $event->parent->getKey(),
            'hierarchy_type' => $event->type,
            'user_id' => auth()->id(),
        ]);
    }

    public function handleMove(NodeMoved $event): void
    {
        AuditLog::create([
            'action' => 'hierarchy_move',
            'node_type' => $event->node->getMorphClass(),
            'node_id' => $event->node->getKey(),
            'from_parent_id' => $event->previousParent?->getKey(),
            'to_parent_id' => $event->newParent?->getKey(),
            'hierarchy_type' => $event->type,
            'user_id' => auth()->id(),
        ]);
    }
}
```

### Notifications

```php
class AncestorNotificationListener
{
    public function handleAttach(NodeAttached $event): void
    {
        $event->parent->notify(new NewDirectReportNotification($event->node));
    }

    public function handleDetach(NodeDetached $event): void
    {
        $event->previousParent->notify(new ReportRemovedNotification($event->node));
    }
}
```

### Cache Invalidation

```php
class AncestorCacheListener
{
    public function handle(NodeAttached|NodeDetached|NodeMoved|NodeRemoved $event): void
    {
        // Clear hierarchy cache for affected nodes
        Cache::tags(['hierarchy', $event->type])->flush();
    }
}
```

### Updating Denormalized Data

```php
class AncestorDenormalizationListener
{
    public function handleAttach(NodeAttached $event): void
    {
        // Update path column for fast queries
        $path = Ancestry::getPath($event->node, $event->type)
            ->pluck('id')
            ->implode('/');

        $event->node->update(['hierarchy_path' => $path]);
    }
}
```

## Disabling Events

Disable events via configuration:

```php
// config/ancestry.php
'events' => [
    'enabled' => false,
],
```

Or via environment variable:

```env
ANCESTRY_EVENTS_ENABLED=false
```

## Testing with Events

### Asserting Events Are Dispatched

```php
use Cline\Ancestry\Events\NodeAttached;
use Illuminate\Support\Facades\Event;

test('dispatches event when attaching', function () {
    Event::fake([NodeAttached::class]);

    $parent = User::create();
    $child = User::create();

    Ancestry::addToAncestry($parent, 'seller');
    Ancestry::addToAncestry($child, 'seller', $parent);

    Event::assertDispatched(NodeAttached::class, function ($event) use ($child, $parent) {
        return $event->node->id === $child->id
            && $event->parent->id === $parent->id;
    });
});
```

### Preventing Events in Tests

```php
test('something without events', function () {
    config()->set('ancestry.events.enabled', false);

    // Your test code...
});
```

<a id="doc-docs-snapshots"></a>

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
