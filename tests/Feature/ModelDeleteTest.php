<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Database\Ancestor;
use Cline\Ancestry\Database\ModelRegistry;
use Cline\Ancestry\Events\NodeAttached;
use Cline\Ancestry\Events\NodeDetached;
use Cline\Ancestry\Events\NodeMoved;
use Cline\Ancestry\Events\NodeRemoved;
use Cline\Ancestry\Events\SnapshotCleared;
use Cline\Ancestry\Exceptions\AncestryException;
use Cline\Ancestry\Exceptions\NodeHasDescendantsException;
use Cline\Ancestry\Facades\Ancestry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\SoftDeletableUser;
use Tests\Fixtures\User;

/**
 * @return list<int|string>
 */
function ancestorRowIds(): array
{
    return DB::table(
        new Ancestor()->getTable(),
    )->orderBy('id')->pluck('id')->all();
}

/**
 * Closure rows that reference the model as ancestor or descendant, queried
 * independently of the code under test.
 *
 * @return list<int|string>
 */
function ancestorRowIdsFor(Model $model): array
{
    $morphClass = $model->getMorphClass();
    $key = resolve(ModelRegistry::class)->getModelKeyValue($model);

    return DB::table(
        new Ancestor()->getTable(),
    )
        ->where(fn ($query) => $query
            ->where(fn ($q) => $q->where('ancestor_type', $morphClass)->where('ancestor_id', $key))
            ->orWhere(fn ($q) => $q->where('descendant_type', $morphClass)->where('descendant_id', $key)))
        ->orderBy('id')
        ->pluck('id')
        ->all();
}

describe('Model delete', function (): void {
    test('deleting a leaf removes its self row and paths to its ancestors only', function (): void {
        [$grandparent, $parent, $leaf] = createAncestorChain(3);
        $sibling = user();
        Ancestry::addToAncestry($sibling, 'seller', $parent);
        $leafRows = ancestorRowIdsFor($leaf);
        $before = ancestorRowIds();

        expect($leafRows)->toHaveCount(3);
        expect(fn () => $parent->delete())->toThrow(NodeHasDescendantsException::class);
        expect(ancestorRowIds())->toBe($before);
        expect($leaf->delete())->toBeTrue();

        expect(User::query()->whereKey($leaf->getKey())->exists())->toBeFalse();
        expect(ancestorRowIdsFor($leaf))->toBe([]);
        expect(ancestorRowIds())->toBe(array_values(array_diff($before, $leafRows)));
        expect(Ancestry::getDescendants($grandparent, 'seller')->modelKeys())
            ->toEqualCanonicalizing([$parent->getKey(), $sibling->getKey()]);
        expect(Ancestry::getDirectChildren($parent, 'seller')->modelKeys())->toBe([$sibling->getKey()]);
    });

    test('deleting a leaf removes its rows in every ancestry type', function (): void {
        $user = user();
        $sellerParent = user();
        Ancestry::addToAncestry($sellerParent, 'seller');
        Ancestry::addToAncestry($user, 'seller', $sellerParent);
        [$resellerRoot, $resellerParent] = createAncestorChain(2, 'reseller');
        Ancestry::addToAncestry($user, 'reseller', $resellerParent);
        Ancestry::addToAncestry($user, 'organization');
        $userRows = ancestorRowIdsFor($user);
        $before = ancestorRowIds();

        expect($userRows)->toHaveCount(6);
        expect($user->delete())->toBeTrue();

        expect(ancestorRowIds())->toBe(array_values(array_diff($before, $userRows)));
        expect(Ancestry::isLeaf($sellerParent, 'seller'))->toBeTrue();
        expect(Ancestry::isLeaf($resellerParent, 'reseller'))->toBeTrue();
        expect(Ancestry::getDescendants($resellerRoot, 'reseller')->modelKeys())->toBe([$resellerParent->getKey()]);
    });

    test('deleting a root without ancestors removes its self row', function (): void {
        $user = user();
        $otherRoot = user();
        Ancestry::addToAncestry($user, 'seller');
        Ancestry::addToAncestry($otherRoot, 'seller');

        expect($user->delete())->toBeTrue();

        expect(ancestorRowIdsFor($user))->toBe([]);
        expect(Ancestry::getRootNodes('seller')->modelKeys())->toBe([$otherRoot->getKey()]);
    });

    test('deleting a model that was never in a hierarchy leaves all rows alone', function (): void {
        createAncestorChain(2);
        $user = user();
        $before = ancestorRowIds();

        expect($user->delete())->toBeTrue();

        expect(ancestorRowIds())->toBe($before);
    });

    test('deleting a model that still has descendants in any type throws and deletes nothing', function (Closure $addDescendant): void {
        $parentInA = user();
        $user = user();
        Ancestry::addToAncestry($parentInA, 'a');
        Ancestry::addToAncestry($user, 'a', $parentInA);
        Ancestry::addToAncestry($user, 'b');
        $descendant = $addDescendant($user);
        Ancestry::addToAncestry($user, 'c');
        $before = ancestorRowIds();

        expect(fn () => $user->delete())->toThrow(
            NodeHasDescendantsException::class,
            sprintf('Cannot delete [%s:%s] - it still has descendants in ancestry types [b].', $user->getMorphClass(), $user->getKey()),
        );

        expect($user->exists)->toBeTrue();
        expect(User::query()->whereKey($user->getKey())->exists())->toBeTrue();
        expect(ancestorRowIds())->toBe($before);
        expect(Ancestry::isAncestorOf($user, $descendant, 'b'))->toBeTrue();
    })->with([
        'a user child' => [function (User $user): Model {
            $child = user();
            Ancestry::addToAncestry($child, 'b', $user);

            return $child;
        }],
        'an order child' => [function (User $user): Model {
            $order = order();
            Ancestry::addToAncestry($order, 'b', $user);

            return $order;
        }],
    ]);

    test('the descendants exception names every blocking type once', function (): void {
        $user = user();
        Ancestry::addToAncestry($user, 'b');
        Ancestry::addToAncestry(user(), 'b', $user);
        Ancestry::addToAncestry(user(), 'b', $user);
        Ancestry::addToAncestry($user, 'a');
        Ancestry::addToAncestry(user(), 'a', $user);

        expect(fn () => $user->delete())->toThrow(
            NodeHasDescendantsException::class,
            sprintf('Cannot delete [%s:%s] - it still has descendants in ancestry types [a, b].', $user->getMorphClass(), $user->getKey()),
        );
    });

    test('the descendants exception is an ancestry exception', function (): void {
        [$parent] = createAncestorChain(2);

        $thrown = null;

        try {
            $parent->delete();
        } catch (Throwable $throwable) {
            $thrown = $throwable;
        }

        expect($thrown)->toBeInstanceOf(AncestryException::class);
    });

    test('a blocked parent can be deleted once its child is deleted', function (): void {
        [$parent, $child] = createAncestorChain(2);

        expect(fn () => $parent->delete())->toThrow(NodeHasDescendantsException::class);
        expect($child->delete())->toBeTrue();
        expect($parent->delete())->toBeTrue();

        expect(ancestorRowIdsFor($parent))->toBe([]);
        expect(ancestorRowIdsFor($child))->toBe([]);
    });

    test('rows left for a descendant that no longer exists still block the delete', function (): void {
        [$parent, $child] = createAncestorChain(2);
        User::query()->whereKey($child->getKey())->delete();
        $before = ancestorRowIds();

        expect(fn () => $parent->delete())->toThrow(NodeHasDescendantsException::class);

        expect(ancestorRowIds())->toBe($before);
    });

    test('rows of another model class with the same key are neither removed nor blocking', function (): void {
        $parent = user();
        $user = user();
        Ancestry::addToAncestry($parent, 'seller');
        Ancestry::addToAncestry($user, 'seller', $parent);
        $order = order(['id' => $user->getKey()]);
        $orderChild = order();
        Ancestry::addToAncestry($order, 'seller');
        Ancestry::addToAncestry($orderChild, 'seller', $order);
        $orderRows = [...ancestorRowIdsFor($order), ...ancestorRowIdsFor($orderChild)];

        expect($order->getKey())->toEqual($user->getKey());
        expect($user->delete())->toBeTrue();

        expect(ancestorRowIdsFor($user))->toBe([]);
        expect(array_values(array_intersect(ancestorRowIds(), $orderRows)))
            ->toEqualCanonicalizing(array_values(array_unique($orderRows)));
    });

    test('a delete cancelled by another listener keeps the rows', function (): void {
        [, $leaf] = createAncestorChain(2);
        $before = ancestorRowIds();
        User::deleting(fn (): bool => false);

        expect($leaf->delete())->toBeFalse();

        expect(User::query()->whereKey($leaf->getKey())->exists())->toBeTrue();
        expect(ancestorRowIds())->toBe($before);
    });

    test('a delete dispatches no ancestry events', function (): void {
        [, $leaf] = createAncestorChain(2);
        $events = [NodeAttached::class, NodeDetached::class, NodeMoved::class, NodeRemoved::class, SnapshotCleared::class];
        Event::fake($events);

        $leaf->delete();

        foreach ($events as $event) {
            Event::assertNotDispatched($event);
        }
    });

    test('deleting a leaf keeps ancestry snapshots that reference it', function (): void {
        [$grandparent, $parent, $leaf] = createAncestorChain(3);
        $order = order();
        $order->snapshotAncestry($leaf, 'seller');

        $snapshotsBefore = DB::table('hierarchy_snapshots')->orderBy('id')->pluck('id')->all();

        $leaf->delete();

        expect(DB::table('hierarchy_snapshots')->orderBy('id')->pluck('id')->all())->toBe($snapshotsBefore);
        expect($order->getAncestrySnapshotAncestorIds('seller'))
            ->toEqual([$leaf->getKey(), $parent->getKey(), $grandparent->getKey()]);
    });

    test('custom morph keys and morph aliases are respected', function (): void {
        resolve(ModelRegistry::class)->morphKeyMap([User::class => 'email']);
        Relation::morphMap(['user' => User::class]);

        try {
            $parent = user(['email' => 'parent@example.com']);
            $child = user(['email' => 'child@example.com']);
            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);
            $before = ancestorRowIds();

            expect(fn () => $parent->delete())->toThrow(NodeHasDescendantsException::class);
            expect(ancestorRowIds())->toBe($before);

            expect($child->delete())->toBeTrue();
            expect(DB::table(
                new Ancestor()->getTable(),
            )
                ->where('descendant_type', 'user')
                ->where('descendant_id', 'child@example.com')
                ->exists())->toBeFalse();
            expect(DB::table(
                new Ancestor()->getTable(),
            )
                ->where('descendant_type', 'user')
                ->where('descendant_id', 'parent@example.com')
                ->exists())->toBeTrue();
        } finally {
            Relation::morphMap([], merge: false);
        }
    })->skip(
        fn (): bool => !in_array(env('ANCESTRY_ANCESTOR_MORPH_TYPE', 'string'), ['morph', 'string'], true),
        'Email keys only fit string morph columns',
    );

    describe('soft deletes', function (): void {
        test('a soft delete keeps the rows so a restore keeps the hierarchy', function (): void {
            $parent = SoftDeletableUser::query()->create();
            $child = SoftDeletableUser::query()->create();
            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);
            $before = ancestorRowIds();

            expect($parent->delete())->toBeTrue();
            expect($child->delete())->toBeTrue();
            expect(ancestorRowIds())->toBe($before);

            $parent->restore();
            $child->restore();

            expect(Ancestry::isAncestorOf($parent, $child, 'seller'))->toBeTrue();
        });

        test('a force delete removes the rows of a leaf', function (): void {
            $parent = SoftDeletableUser::query()->create();
            $child = SoftDeletableUser::query()->create();
            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);

            expect($child->forceDelete())->toBeTrue();

            expect(ancestorRowIdsFor($child))->toBe([]);
            expect(ancestorRowIdsFor($parent))->toHaveCount(1);
        });

        test('a force delete of a model with descendants throws and deletes nothing', function (): void {
            $parent = SoftDeletableUser::query()->create();
            $child = SoftDeletableUser::query()->create();
            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);
            $before = ancestorRowIds();

            expect(fn () => $parent->forceDelete())->toThrow(NodeHasDescendantsException::class);

            expect(SoftDeletableUser::withTrashed()->whereKey($parent->getKey())->exists())->toBeTrue();
            expect(ancestorRowIds())->toBe($before);
        });

        test('a blocked force delete leaves the model able to soft delete', function (): void {
            $parent = SoftDeletableUser::query()->create();
            $child = SoftDeletableUser::query()->create();
            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);
            $before = ancestorRowIds();

            expect(fn () => $parent->forceDelete())->toThrow(NodeHasDescendantsException::class);
            expect($parent->isForceDeleting())->toBeFalse();

            $child->forceDelete();

            expect($parent->delete())->toBeTrue();
            expect($parent->trashed())->toBeTrue();
            expect(SoftDeletableUser::withTrashed()->whereKey($parent->getKey())->exists())->toBeTrue();
            expect(ancestorRowIdsFor($parent))->toBe(array_values(array_intersect($before, ancestorRowIdsFor($parent))));
            expect(ancestorRowIdsFor($parent))->toHaveCount(1);
        });
    });
});
