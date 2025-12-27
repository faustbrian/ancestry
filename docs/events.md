---
title: Events
description: React to hierarchy changes with Ancestry's event system for Laravel applications.
---

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
