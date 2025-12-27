---
title: Getting Started
description: Install and configure Ancestry for closure table hierarchies in Laravel.
---

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

- [Basic Usage](/ancestry/basic-usage/) - Learn the core operations
- [Fluent API](/ancestry/fluent-api/) - Master the chainable interface
- [Configuration](/ancestry/configuration/) - Customize Ancestry for your needs
- [Multiple Ancestor Types](/ancestry/multiple-types/) - Manage different hierarchies
