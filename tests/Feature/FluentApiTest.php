<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Facades\Ancestry;

describe('Fluent API - for() Conductor', function (): void {
    test('can add to hierarchy', function (): void {
        $user = user();

        Ancestry::for($user)->type('seller')->add();

        expect(Ancestry::isInAncestry($user, 'seller'))->toBeTrue();
    });

    test('can add with parent', function (): void {
        $parent = user();
        $child = user();

        Ancestry::for($parent)->type('seller')->add();
        Ancestry::for($child)->type('seller')->add($parent);

        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can attach to parent', function (): void {
        $parent = user();
        $child = user();

        Ancestry::for($parent)->type('seller')->add();
        Ancestry::for($child)->type('seller')->add();
        Ancestry::for($child)->type('seller')->attachTo($parent);

        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can detach', function (): void {
        [$parent, $child] = createAncestorChain(2);

        Ancestry::for($child)->type('seller')->detach();

        expect(Ancestry::isRoot($child, 'seller'))->toBeTrue();
    });

    test('can remove', function (): void {
        [$parent, $child] = createAncestorChain(2);

        Ancestry::for($child)->type('seller')->remove();

        expect(Ancestry::isInAncestry($child, 'seller'))->toBeFalse();
    });

    test('can move to new parent', function (): void {
        $root1 = user();
        $root2 = user();
        $child = user();

        Ancestry::for($root1)->type('seller')->add();
        Ancestry::for($root2)->type('seller')->add();
        Ancestry::for($child)->type('seller')->add($root1);

        Ancestry::for($child)->type('seller')->moveTo($root2);

        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($root2->id);
    });

    test('can get ancestors', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $ancestors = Ancestry::for($child)->type('seller')->ancestors();

        expect($ancestors)->toHaveCount(2);
    });

    test('can get descendants', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $descendants = Ancestry::for($grandparent)->type('seller')->descendants();

        expect($descendants)->toHaveCount(2);
    });

    test('can get parent', function (): void {
        [$parent, $child] = createAncestorChain(2);

        $result = Ancestry::for($child)->type('seller')->parent();

        expect($result->id)->toBe($parent->id);
    });

    test('can get children', function (): void {
        [$parent, $child] = createAncestorChain(2);

        $children = Ancestry::for($parent)->type('seller')->children();

        expect($children)->toHaveCount(1);
        expect($children->first()->id)->toBe($child->id);
    });

    test('can check is ancestor of', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect(Ancestry::for($parent)->type('seller')->isAncestorOf($child))->toBeTrue();
        expect(Ancestry::for($child)->type('seller')->isAncestorOf($parent))->toBeFalse();
    });

    test('can check is descendant of', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect(Ancestry::for($child)->type('seller')->isDescendantOf($parent))->toBeTrue();
        expect(Ancestry::for($parent)->type('seller')->isDescendantOf($child))->toBeFalse();
    });

    test('can get depth', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        expect(Ancestry::for($grandparent)->type('seller')->depth())->toBe(0);
        expect(Ancestry::for($parent)->type('seller')->depth())->toBe(1);
        expect(Ancestry::for($child)->type('seller')->depth())->toBe(2);
    });

    test('can get roots', function (): void {
        [$root, $parent, $child] = createAncestorChain(3);

        $roots = Ancestry::for($child)->type('seller')->roots();

        expect($roots)->toHaveCount(1);
        expect($roots->first()->id)->toBe($root->id);
    });

    test('can build tree', function (): void {
        [$root, $child1] = createAncestorChain(2);
        $child2 = user();
        Ancestry::addToAncestry($child2, 'seller', $root);

        $tree = Ancestry::for($root)->type('seller')->tree();

        expect($tree['model']->id)->toBe($root->id);
        expect($tree['children'])->toHaveCount(2);
    });

    test('can get path', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $path = Ancestry::for($child)->type('seller')->path();

        expect($path)->toHaveCount(3);
        expect($path->pluck('id')->toArray())->toBe([$grandparent->id, $parent->id, $child->id]);
    });

    test('can check is in hierarchy', function (): void {
        $user = user();
        Ancestry::addToAncestry($user, 'seller');

        expect(Ancestry::for($user)->type('seller')->isInAncestry())->toBeTrue();
        expect(Ancestry::for($user)->type('reseller')->isInAncestry())->toBeFalse();
    });

    test('can check is root', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect(Ancestry::for($parent)->type('seller')->isRoot())->toBeTrue();
        expect(Ancestry::for($child)->type('seller')->isRoot())->toBeFalse();
    });

    test('can check is leaf', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect(Ancestry::for($child)->type('seller')->isLeaf())->toBeTrue();
        expect(Ancestry::for($parent)->type('seller')->isLeaf())->toBeFalse();
    });

    test('can get siblings', function (): void {
        $parent = user();
        $child1 = user();
        $child2 = user();

        Ancestry::addToAncestry($parent, 'seller');
        Ancestry::addToAncestry($child1, 'seller', $parent);
        Ancestry::addToAncestry($child2, 'seller', $parent);

        $siblings = Ancestry::for($child1)->type('seller')->siblings();

        expect($siblings)->toHaveCount(1);
        expect($siblings->first()->id)->toBe($child2->id);
    });
});

describe('Fluent API - ofType() Conductor', function (): void {
    test('can get roots', function (): void {
        $root1 = user();
        $root2 = user();
        $child = user();

        Ancestry::addToAncestry($root1, 'seller');
        Ancestry::addToAncestry($root2, 'seller');
        Ancestry::addToAncestry($child, 'seller', $root1);

        $roots = Ancestry::ofType('seller')->roots();

        expect($roots)->toHaveCount(2);
    });

    test('can add model', function (): void {
        $parent = user();
        $child = user();

        Ancestry::ofType('seller')->add($parent);
        Ancestry::ofType('seller')->add($child, $parent);

        expect(Ancestry::isInAncestry($child, 'seller'))->toBeTrue();
        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can get model conductor', function (): void {
        $user = user();
        Ancestry::addToAncestry($user, 'seller');

        $conductor = Ancestry::ofType('seller')->for($user);

        expect($conductor->isInAncestry())->toBeTrue();
    });
});
