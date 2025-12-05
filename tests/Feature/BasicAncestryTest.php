<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Database\Ancestor;
use Cline\Ancestry\Facades\Ancestry;

describe('AncestryManager Direct Methods', function (): void {
    test('getRoots returns all root ancestors for a model', function (): void {
        [$root, $parent, $child] = createAncestorChain(3);

        $roots = Ancestry::getRoots($child, 'seller');

        expect($roots)->toHaveCount(1);
        expect($roots->first()->id)->toBe($root->id);
    });

    test('getRoots returns self when model is root', function (): void {
        $root = user();
        Ancestry::addToAncestry($root, 'seller');

        $roots = Ancestry::getRoots($root, 'seller');

        expect($roots)->toHaveCount(1);
        expect($roots->first()->id)->toBe($root->id);
    });

    test('buildTree creates tree structure from model', function (): void {
        [$root, $child1] = createAncestorChain(2);
        $child2 = user();
        Ancestry::addToAncestry($child2, 'seller', $root);

        $tree = Ancestry::buildTree($root, 'seller');

        expect($tree['model']->id)->toBe($root->id);
        expect($tree['children'])->toHaveCount(2);
    });

    test('getPath returns path from root to model', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $path = Ancestry::getPath($child, 'seller');

        expect($path)->toHaveCount(3);
        expect($path->pluck('id')->toArray())->toBe([$grandparent->id, $parent->id, $child->id]);
    });

    test('getSiblings returns sibling models', function (): void {
        $parent = user();
        $child1 = user();
        $child2 = user();

        Ancestry::addToAncestry($parent, 'seller');
        Ancestry::addToAncestry($child1, 'seller', $parent);
        Ancestry::addToAncestry($child2, 'seller', $parent);

        $siblings = Ancestry::getSiblings($child1, 'seller');

        expect($siblings)->toHaveCount(1);
        expect($siblings->first()->id)->toBe($child2->id);
    });

    test('getSiblings with includeSelf returns self too', function (): void {
        $parent = user();
        $child1 = user();
        $child2 = user();

        Ancestry::addToAncestry($parent, 'seller');
        Ancestry::addToAncestry($child1, 'seller', $parent);
        Ancestry::addToAncestry($child2, 'seller', $parent);

        $siblings = Ancestry::getSiblings($child1, 'seller', includeSelf: true);

        expect($siblings)->toHaveCount(2);
    });

    test('getSiblings for root returns other roots', function (): void {
        $root1 = user();
        $root2 = user();
        $root3 = user();

        Ancestry::addToAncestry($root1, 'seller');
        Ancestry::addToAncestry($root2, 'seller');
        Ancestry::addToAncestry($root3, 'seller');

        $siblings = Ancestry::getSiblings($root1, 'seller');

        expect($siblings)->toHaveCount(2);
        expect($siblings->pluck('id')->sort()->values()->toArray())
            ->toBe(collect([$root2->id, $root3->id])->sort()->values()->all());
    });

    test('getSiblings for root with includeSelf returns all roots', function (): void {
        $root1 = user();
        $root2 = user();

        Ancestry::addToAncestry($root1, 'seller');
        Ancestry::addToAncestry($root2, 'seller');

        $siblings = Ancestry::getSiblings($root1, 'seller', includeSelf: true);

        expect($siblings)->toHaveCount(2);
    });
});

describe('Basic Ancestor Operations', function (): void {
    test('can add a model to a hierarchy as root', function (): void {
        $user = user();

        Ancestry::addToAncestry($user, 'seller');

        expect(Ancestry::isInAncestry($user, 'seller'))->toBeTrue();
        expect(Ancestry::isRoot($user, 'seller'))->toBeTrue();
        expect(Ancestry::getDepth($user, 'seller'))->toBe(0);
    });

    test('can add a model to a hierarchy with parent', function (): void {
        $parent = user();
        $child = user();

        Ancestry::addToAncestry($parent, 'seller');
        Ancestry::addToAncestry($child, 'seller', $parent);

        expect(Ancestry::isInAncestry($child, 'seller'))->toBeTrue();
        expect(Ancestry::isRoot($child, 'seller'))->toBeFalse();
        expect(Ancestry::getDepth($child, 'seller'))->toBe(1);
        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
    });

    test('can get ancestors', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $ancestors = Ancestry::getAncestors($child, 'seller');

        expect($ancestors)->toHaveCount(2);
        expect($ancestors->pluck('id')->toArray())->toBe([$parent->id, $grandparent->id]);
    });

    test('can get ancestors including self', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $ancestors = Ancestry::getAncestors($child, 'seller', includeSelf: true);

        expect($ancestors)->toHaveCount(3);
        expect($ancestors->first()->id)->toBe($child->id);
    });

    test('can get ancestors with max depth', function (): void {
        [$greatGrandparent, $grandparent, $parent, $child] = createAncestorChain(4);

        $ancestors = Ancestry::getAncestors($child, 'seller', maxDepth: 2);

        // Should get parent (depth 1) and grandparent (depth 2), but not great-grandparent (depth 3)
        expect($ancestors)->toHaveCount(2);
    });

    test('can get descendants', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        $descendants = Ancestry::getDescendants($grandparent, 'seller');

        expect($descendants)->toHaveCount(2);
        expect($descendants->pluck('id')->toArray())->toBe([$parent->id, $child->id]);
    });

    test('can get descendants with max depth', function (): void {
        [$greatGrandparent, $grandparent, $parent, $child] = createAncestorChain(4);

        $descendants = Ancestry::getDescendants($greatGrandparent, 'seller', maxDepth: 2);

        // Should get grandparent (depth 1) and parent (depth 2), but not child (depth 3)
        expect($descendants)->toHaveCount(2);
    });

    test('can get direct children', function (): void {
        $parent = user();
        $child1 = user();
        $child2 = user();
        $grandchild = user();

        Ancestry::addToAncestry($parent, 'seller');
        Ancestry::addToAncestry($child1, 'seller', $parent);
        Ancestry::addToAncestry($child2, 'seller', $parent);
        Ancestry::addToAncestry($grandchild, 'seller', $child1);

        $children = Ancestry::getDirectChildren($parent, 'seller');

        expect($children)->toHaveCount(2);
        expect($children->pluck('id')->sort()->values()->toArray())
            ->toBe(collect([$child1->id, $child2->id])->sort()->values()->all());
    });

    test('can check if model is ancestor of another', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        expect(Ancestry::isAncestorOf($grandparent, $child, 'seller'))->toBeTrue();
        expect(Ancestry::isAncestorOf($parent, $child, 'seller'))->toBeTrue();
        expect(Ancestry::isAncestorOf($child, $grandparent, 'seller'))->toBeFalse();
    });

    test('can check if model is descendant of another', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        expect(Ancestry::isDescendantOf($child, $grandparent, 'seller'))->toBeTrue();
        expect(Ancestry::isDescendantOf($child, $parent, 'seller'))->toBeTrue();
        expect(Ancestry::isDescendantOf($grandparent, $child, 'seller'))->toBeFalse();
    });

    test('can check if model is leaf', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect(Ancestry::isLeaf($child, 'seller'))->toBeTrue();
        expect(Ancestry::isLeaf($parent, 'seller'))->toBeFalse();
    });

    test('can get root nodes for a type', function (): void {
        $root1 = user();
        $root2 = user();
        $child = user();

        Ancestry::addToAncestry($root1, 'seller');
        Ancestry::addToAncestry($root2, 'seller');
        Ancestry::addToAncestry($child, 'seller', $root1);

        $roots = Ancestry::getRootNodes('seller');

        expect($roots)->toHaveCount(2);
        expect($roots->pluck('id')->sort()->values()->toArray())
            ->toBe(collect([$root1->id, $root2->id])->sort()->values()->all());
    });

    test('hierarchies are isolated by type', function (): void {
        $user = user();

        Ancestry::addToAncestry($user, 'seller');
        Ancestry::addToAncestry($user, 'reseller');

        expect(Ancestry::isInAncestry($user, 'seller'))->toBeTrue();
        expect(Ancestry::isInAncestry($user, 'reseller'))->toBeTrue();
        expect(Ancestry::isInAncestry($user, 'organization'))->toBeFalse();
    });

    test('can add child to parent who is not yet in hierarchy', function (): void {
        // Regression test: parent not in hierarchy should auto-create parent's self-reference
        $parent = user();
        $child = user();

        // Parent is NOT added to hierarchy first - this is the regression case
        Ancestry::addToAncestry($child, 'seller', $parent);

        // Both should be in hierarchy
        expect(Ancestry::isInAncestry($parent, 'seller'))->toBeTrue();
        expect(Ancestry::isInAncestry($child, 'seller'))->toBeTrue();

        // Parent should be root, child should have parent
        expect(Ancestry::isRoot($parent, 'seller'))->toBeTrue();
        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
        expect(Ancestry::getDepth($child, 'seller'))->toBe(1);
    });

    test('can attach to parent who is not yet in hierarchy', function (): void {
        // Regression test: attachToParent with parent not in hierarchy
        $parent = user();
        $child = user();

        // Child exists as root
        Ancestry::addToAncestry($child, 'seller');

        // Parent is NOT in hierarchy - attach should auto-create parent's self-reference
        Ancestry::attachToParent($child, $parent, 'seller');

        // Both should be in hierarchy with correct relationship
        expect(Ancestry::isInAncestry($parent, 'seller'))->toBeTrue();
        expect(Ancestry::getDirectParent($child, 'seller')->id)->toBe($parent->id);
        expect(Ancestry::getDepth($child, 'seller'))->toBe(1);
    });
});

describe('Ancestor Database Records', function (): void {
    test('creates self-reference record', function (): void {
        $user = user();

        Ancestry::addToAncestry($user, 'seller');

        $record = Ancestor::query()
            ->where('ancestor_id', $user->id)
            ->where('descendant_id', $user->id)
            ->where('depth', 0)
            ->first();

        expect($record)->not->toBeNull();
        expect($record->type)->toBe('seller');
    });

    test('creates correct closure table records for chain', function (): void {
        [$grandparent, $parent, $child] = createAncestorChain(3);

        // Grandparent has 3 descendant records (self, parent, child)
        $grandparentRecords = Ancestor::query()
            ->where('ancestor_id', $grandparent->id)
            ->where('type', 'seller')
            ->get();

        expect($grandparentRecords)->toHaveCount(3);
        expect($grandparentRecords->pluck('depth')->sort()->values()->toArray())->toBe([0, 1, 2]);

        // Child has 3 ancestor records (self, parent, grandparent)
        $childRecords = Ancestor::query()
            ->where('descendant_id', $child->id)
            ->where('type', 'seller')
            ->get();

        expect($childRecords)->toHaveCount(3);
    });

    test('hierarchy model has ancestorRelation morphTo', function (): void {
        [$parent, $child] = createAncestorChain(2);

        $record = Ancestor::query()
            ->where('ancestor_id', $parent->id)
            ->where('descendant_id', $child->id)
            ->where('depth', 1)
            ->first();

        expect($record->ancestorRelation)->not->toBeNull();
        expect($record->ancestorRelation->id)->toBe($parent->id);
    });

    test('hierarchy model has descendantRelation morphTo', function (): void {
        [$parent, $child] = createAncestorChain(2);

        $record = Ancestor::query()
            ->where('ancestor_id', $parent->id)
            ->where('descendant_id', $child->id)
            ->where('depth', 1)
            ->first();

        expect($record->descendantRelation)->not->toBeNull();
        expect($record->descendantRelation->id)->toBe($child->id);
    });
});
