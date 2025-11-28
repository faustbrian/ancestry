<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Exceptions\CircularReferenceException;
use Cline\Ancestry\Exceptions\MaxDepthExceededException;
use Cline\Ancestry\Facades\Ancestry;

describe('Ancestor Validation', function (): void {
    test('prevents circular reference when attaching', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        expect(fn () => Ancestry::attachToParent($grandparent, $child, 'seller'))
            ->toThrow(CircularReferenceException::class);
    });

    test('prevents circular reference when moving', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        expect(fn () => Ancestry::moveToParent($grandparent, $child, 'seller'))
            ->toThrow(CircularReferenceException::class);
    });

    test('prevents exceeding max depth', function (): void {
        config()->set('ancestry.max_depth', 3);

        // Create a chain at max depth
        $users = createAncestorChain(4); // This creates depth 3 (0, 1, 2, 3)

        // Try to add another level
        $newUser = user();

        expect(fn () => Ancestry::addToAncestry($newUser, 'seller', end($users)))
            ->toThrow(MaxDepthExceededException::class);
    });

    test('allows unlimited depth when max_depth is null', function (): void {
        config()->set('ancestry.max_depth');

        // Create a deep chain
        $users = createAncestorChain(15);

        expect(Ancestry::getDepth(end($users), 'seller'))->toBe(14);
    });
});
