<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Facades\Ancestry;

describe('Ancestor Modification Operations', function (): void {
    test('can detach from parent', function (): void {
        [$parent, $child] = createAncestorChain(2);

        Ancestry::detachFromParent($child, 'seller');

        expect(Ancestry::isInAncestry($child, 'seller'))->toBeTrue();
        expect(Ancestry::isRoot($child, 'seller'))->toBeTrue();
        expect(Ancestry::getDirectParent($child, 'seller'))->toBeNull();
    });

    test('can remove from hierarchy completely', function (): void {
        [$parent, $child, $grandchild] = createAncestorChain(3);

        Ancestry::removeFromAncestry($child, 'seller');

        expect(Ancestry::isInAncestry($child, 'seller'))->toBeFalse();
        // Grandchild should also lose its ancestor paths through child
        expect(Ancestry::isDescendantOf($grandchild, $parent, 'seller'))->toBeFalse();
    });

    test('can move to new parent', function (): void {
        $root1 = user();
        $root2 = user();
        $child = user();

        Ancestry::addToAncestry($root1, 'seller');
        Ancestry::addToAncestry($root2, 'seller');
        Ancestry::addToAncestry($child, 'seller', $root1);

        Ancestry::moveToParent($child, $root2, 'seller');

        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($root2->id);
        expect(Ancestry::isDescendantOf($child, $root1, 'seller'))->toBeFalse();
        expect(Ancestry::isDescendantOf($child, $root2, 'seller'))->toBeTrue();
    });

    test('can move to become root', function (): void {
        [$parent, $child] = createAncestorChain(2);

        Ancestry::moveToParent($child, null, 'seller');

        expect(Ancestry::isRoot($child, 'seller'))->toBeTrue();
        expect(Ancestry::isDescendantOf($child, $parent, 'seller'))->toBeFalse();
    });

    test('can attach to parent', function (): void {
        $parent = user();
        $child = user();

        Ancestry::addToAncestry($parent, 'seller');
        Ancestry::addToAncestry($child, 'seller');

        expect(Ancestry::isRoot($child, 'seller'))->toBeTrue();

        Ancestry::attachToParent($child, $parent, 'seller');

        expect(Ancestry::isRoot($child, 'seller'))->toBeFalse();
        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can attach model not in hierarchy to parent', function (): void {
        $parent = user();
        $child = user();

        // Only add parent to hierarchy, NOT child
        Ancestry::addToAncestry($parent, 'seller');

        // Child is not in any hierarchy yet
        expect(Ancestry::isInAncestry($child, 'seller'))->toBeFalse();

        // Attach child directly to parent (triggers ensureSelfReference)
        Ancestry::attachToParent($child, $parent, 'seller');

        expect(Ancestry::isInAncestry($child, 'seller'))->toBeTrue();
        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });
});

describe('Move with Descendants', function (): void {
    test('moving subtree preserves descendant relationships', function (): void {
        $root1 = user();
        $root2 = user();
        $parent = user();
        $child = user();
        $grandchild = user();

        Ancestry::addToAncestry($root1, 'seller');
        Ancestry::addToAncestry($root2, 'seller');
        Ancestry::addToAncestry($parent, 'seller', $root1);
        Ancestry::addToAncestry($child, 'seller', $parent);
        Ancestry::addToAncestry($grandchild, 'seller', $child);

        // Move the subtree from root1 to root2
        Ancestry::moveToParent($parent, $root2, 'seller');

        // Verify new ancestry
        expect(Ancestry::isDescendantOf($parent, $root2, 'seller'))->toBeTrue();
        expect(Ancestry::isDescendantOf($child, $root2, 'seller'))->toBeTrue();
        expect(Ancestry::isDescendantOf($grandchild, $root2, 'seller'))->toBeTrue();

        // Verify old ancestry is gone
        expect(Ancestry::isDescendantOf($parent, $root1, 'seller'))->toBeFalse();
        expect(Ancestry::isDescendantOf($child, $root1, 'seller'))->toBeFalse();
        expect(Ancestry::isDescendantOf($grandchild, $root1, 'seller'))->toBeFalse();

        // Verify internal subtree relationships preserved
        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
        expect(Ancestry::getDirectParent($grandchild, 'seller')->id)->toBe($child->id);
    });

    test('moving middle node in deep chain preserves all descendant relationships', function (): void {
        // Regression test: when moving a node that has descendants, all descendant
        // relationships must be preserved (parent map must be cached BEFORE deletion)
        $chain = createAncestorChain(5); // u1 -> u2 -> u3 -> u4 -> u5
        [$u1, $u2, $u3, $u4, $u5] = $chain;

        $newRoot = user();
        Ancestry::addToAncestry($newRoot, 'seller');

        // Move u2 (which has u3, u4, u5 as descendants) to newRoot
        Ancestry::moveToParent($u2, $newRoot, 'seller');

        // u2 should now be under newRoot
        expect(Ancestry::getDirectParent($u2, 'seller')->id)->toBe($newRoot->id);

        // All descendants should maintain their direct parent relationships
        expect(Ancestry::getDirectParent($u3, 'seller')->id)->toBe($u2->id);
        expect(Ancestry::getDirectParent($u4, 'seller')->id)->toBe($u3->id);
        expect(Ancestry::getDirectParent($u5, 'seller')->id)->toBe($u4->id);

        // All descendants should have newRoot as ancestor
        expect(Ancestry::isDescendantOf($u3, $newRoot, 'seller'))->toBeTrue();
        expect(Ancestry::isDescendantOf($u4, $newRoot, 'seller'))->toBeTrue();
        expect(Ancestry::isDescendantOf($u5, $newRoot, 'seller'))->toBeTrue();

        // None should have u1 as ancestor anymore
        expect(Ancestry::isDescendantOf($u2, $u1, 'seller'))->toBeFalse();
        expect(Ancestry::isDescendantOf($u3, $u1, 'seller'))->toBeFalse();

        // Depths should be correct
        expect(Ancestry::getDepth($u2, 'seller'))->toBe(1);
        expect(Ancestry::getDepth($u3, 'seller'))->toBe(2);
        expect(Ancestry::getDepth($u4, 'seller'))->toBe(3);
        expect(Ancestry::getDepth($u5, 'seller'))->toBe(4);
    });
});
