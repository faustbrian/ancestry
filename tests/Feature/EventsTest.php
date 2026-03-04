<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Events\NodeAttached;
use Cline\Ancestry\Events\NodeDetached;
use Cline\Ancestry\Events\NodeMoved;
use Cline\Ancestry\Events\NodeRemoved;
use Cline\Ancestry\Facades\Ancestry;
use Illuminate\Support\Facades\Event;

describe('Ancestor Events', function (): void {
    test('dispatches NodeAttached event when attaching to parent', function (): void {
        Event::fake([NodeAttached::class]);

        $parent = user();
        $child = user();

        Ancestry::addToAncestry($parent, 'seller');
        Ancestry::addToAncestry($child, 'seller', $parent);

        Event::assertDispatched(NodeAttached::class, fn (NodeAttached $event): bool => $event->node->id === $child->id
            && $event->parent->id === $parent->id
            && $event->type === 'seller');
    });

    test('dispatches NodeDetached event when detaching from parent', function (): void {
        Event::fake([NodeDetached::class]);

        [$parent, $child] = createAncestorChain(2);

        Ancestry::detachFromParent($child, 'seller');

        Event::assertDispatched(NodeDetached::class, fn (NodeDetached $event): bool => $event->node->id === $child->id
            && $event->previousParent->id === $parent->id
            && $event->type === 'seller');
    });

    test('dispatches NodeMoved event when moving to new parent', function (): void {
        Event::fake([NodeMoved::class]);

        $root1 = user();
        $root2 = user();
        $child = user();

        Ancestry::addToAncestry($root1, 'seller');
        Ancestry::addToAncestry($root2, 'seller');
        Ancestry::addToAncestry($child, 'seller', $root1);

        Event::fake([NodeMoved::class]); // Reset after setup

        Ancestry::moveToParent($child, $root2, 'seller');

        Event::assertDispatched(NodeMoved::class, fn (NodeMoved $event): bool => $event->node->id === $child->id
            && $event->previousParent->id === $root1->id
            && $event->newParent->id === $root2->id
            && $event->type === 'seller');
    });

    test('dispatches NodeRemoved event when removing from hierarchy', function (): void {
        Event::fake([NodeRemoved::class]);

        $user = user();
        Ancestry::addToAncestry($user, 'seller');

        Event::fake([NodeRemoved::class]); // Reset after setup

        Ancestry::removeFromAncestry($user, 'seller');

        Event::assertDispatched(NodeRemoved::class, fn (NodeRemoved $event): bool => $event->node->id === $user->id
            && $event->type === 'seller');
    });

    test('events can be disabled via config', function (): void {
        config()->set('ancestry.events.enabled', false);

        Event::fake([NodeAttached::class]);

        $parent = user();
        $child = user();

        Ancestry::addToAncestry($parent, 'seller');
        Ancestry::addToAncestry($child, 'seller', $parent);

        Event::assertNotDispatched(NodeAttached::class);
    });
});
