<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Facades\Ancestry;

describe('HasAncestry Trait', function (): void {
    test('can add to ancestry', function (): void {
        $user = user();

        $user->addToAncestry('seller');

        expect(Ancestry::isInAncestry($user, 'seller'))->toBeTrue();
    });

    test('can add to ancestry with parent', function (): void {
        $parent = user();
        $child = user();

        $parent->addToAncestry('seller');
        $child->addToAncestry('seller', $parent);

        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can attach to ancestry parent', function (): void {
        $parent = user();
        $child = user();

        $parent->addToAncestry('seller');
        $child->addToAncestry('seller');
        $child->attachToAncestryParent($parent, 'seller');

        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can detach from ancestry parent', function (): void {
        [$parent, $child] = createAncestorChain(2);

        $child->detachFromAncestryParent('seller');

        expect(Ancestry::isRoot($child, 'seller'))->toBeTrue();
    });

    test('can remove from ancestry', function (): void {
        $user = user();
        $user->addToAncestry('seller');

        $user->removeFromAncestry('seller');

        expect(Ancestry::isInAncestry($user, 'seller'))->toBeFalse();
    });

    test('can move to ancestry parent', function (): void {
        $root1 = user();
        $root2 = user();
        $child = user();

        $root1->addToAncestry('seller');
        $root2->addToAncestry('seller');
        $child->addToAncestry('seller', $root1);

        $child->moveToAncestryParent($root2, 'seller');

        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($root2->id);
    });

    test('can get ancestry ancestors', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $ancestors = $child->getAncestryAncestors('seller');

        expect($ancestors)->toHaveCount(2);
    });

    test('can get ancestry descendants', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $descendants = $grandparent->getAncestryDescendants('seller');

        expect($descendants)->toHaveCount(2);
    });

    test('can get ancestry parent', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect($child->getAncestryParent('seller')->id)->toBe($parent->id);
    });

    test('can get ancestry children', function (): void {
        [$parent, $child] = createAncestorChain(2);

        $children = $parent->getAncestryChildren('seller');

        expect($children)->toHaveCount(1);
        expect($children->first()->id)->toBe($child->id);
    });

    test('can check is ancestry ancestor of', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect($parent->isAncestryAncestorOf($child, 'seller'))->toBeTrue();
        expect($child->isAncestryAncestorOf($parent, 'seller'))->toBeFalse();
    });

    test('can check is ancestry descendant of', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect($child->isAncestryDescendantOf($parent, 'seller'))->toBeTrue();
        expect($parent->isAncestryDescendantOf($child, 'seller'))->toBeFalse();
    });

    test('can get ancestry depth', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        expect($grandparent->getAncestryDepth('seller'))->toBe(0);
        expect($parent->getAncestryDepth('seller'))->toBe(1);
        expect($child->getAncestryDepth('seller'))->toBe(2);
    });

    test('can get ancestry roots', function (): void {
        [$root, $parent, $child] = createAncestorChain(3);

        $roots = $child->getAncestryRoots('seller');

        expect($roots)->toHaveCount(1);
        expect($roots->first()->id)->toBe($root->id);
    });

    test('can build ancestry tree', function (): void {
        [$root, $child] = createAncestorChain(2);

        $tree = $root->buildAncestryTree('seller');

        expect($tree['model']->id)->toBe($root->id);
        expect($tree['children'])->toHaveCount(1);
    });

    test('can get ancestry path', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $path = $child->getAncestryPath('seller');

        expect($path)->toHaveCount(3);
    });

    test('can check is in ancestry', function (): void {
        $user = user();
        $user->addToAncestry('seller');

        expect($user->isInAncestry('seller'))->toBeTrue();
        expect($user->isInAncestry('reseller'))->toBeFalse();
    });

    test('can check is ancestry root', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect($parent->isAncestryRoot('seller'))->toBeTrue();
        expect($child->isAncestryRoot('seller'))->toBeFalse();
    });

    test('can check is ancestry leaf', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect($child->isAncestryLeaf('seller'))->toBeTrue();
        expect($parent->isAncestryLeaf('seller'))->toBeFalse();
    });

    test('can get ancestry siblings', function (): void {
        $parent = user();
        $child1 = user();
        $child2 = user();

        $parent->addToAncestry('seller');
        $child1->addToAncestry('seller', $parent);
        $child2->addToAncestry('seller', $parent);

        $siblings = $child1->getAncestrySiblings('seller');

        expect($siblings)->toHaveCount(1);
        expect($siblings->first()->id)->toBe($child2->id);
    });

    test('can access ancestryAsAncestor relation', function (): void {
        [$parent, $child] = createAncestorChain(2);

        $ancestorEntries = $parent->ancestryAsAncestor;

        // Parent is ancestor of itself (depth 0) and child (depth 1)
        expect($ancestorEntries)->toHaveCount(2);
    });

    test('can access ancestryAsDescendant relation', function (): void {
        [$parent, $child] = createAncestorChain(2);

        $descendantEntries = $child->ancestryAsDescendant;

        // Child is descendant of parent (depth 1) and itself (depth 0)
        expect($descendantEntries)->toHaveCount(2);
    });
});
