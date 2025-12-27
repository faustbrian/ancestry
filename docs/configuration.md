---
title: Configuration
description: Configure Ancestry with all available options for your Laravel application.
---

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
