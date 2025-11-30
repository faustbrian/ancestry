<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Database\ModelRegistry;
use Cline\Ancestry\Facades\Ancestry;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Drop and recreate hierarchies table with morph columns based on config
    $ancestorMorphType = config('ancestry.ancestor_morph_type', 'string');
    $descendantMorphType = config('ancestry.descendant_morph_type', 'string');
    $primaryKeyType = config('ancestry.primary_key_type', 'id');

    Schema::dropIfExists('ancestors');
    Schema::create('ancestors', function ($table) use ($ancestorMorphType, $descendantMorphType, $primaryKeyType): void {
        match ($primaryKeyType) {
            'ulid' => $table->ulid('id')->primary(),
            'uuid' => $table->uuid('id')->primary(),
            default => $table->id(),
        };

        match ($ancestorMorphType) {
            'ulid' => $table->ulidMorphs('ancestor'),
            'uuid' => $table->uuidMorphs('ancestor'),
            'numeric' => $table->numericMorphs('ancestor'),
            default => $table->morphs('ancestor'),
        };

        match ($descendantMorphType) {
            'ulid' => $table->ulidMorphs('descendant'),
            'uuid' => $table->uuidMorphs('descendant'),
            'numeric' => $table->numericMorphs('descendant'),
            default => $table->morphs('descendant'),
        };

        $table->unsignedInteger('depth');
        $table->string('type');
        $table->timestamps();

        $table->index(['ancestor_type', 'ancestor_id', 'type']);
        $table->index(['descendant_type', 'descendant_id', 'type']);
        $table->index(['type', 'depth']);
    });

    // Clear morph map and disable enforcement for these tests
    Relation::morphMap([], merge: false);
    Relation::requireMorphMap(false);

    $this->registry = app(ModelRegistry::class);
    $this->registry->reset();
});

afterEach(function (): void {
    $this->registry->reset();
    Relation::morphMap([], merge: false);
});

describe('MorphKeyIntegration', function (): void {
    describe('Happy Paths', function (): void {
        test('uses correct key for ancestor/descendant id when adding to hierarchy with default id', function (): void {
            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'id',
            ]);
            $parent = User::query()->create(['name' => 'Parent', 'email' => 'parent@example.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'child@example.com']);

            // Act
            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);

            // Assert
            $record = DB::table('ancestors')
                ->where('ancestor_type', $parent->getMorphClass())
                ->where('descendant_type', $child->getMorphClass())
                ->where('depth', 1)
                ->first();

            expect($record)->not->toBeNull();
            expect($record->ancestor_id)->toEqual((string) $parent->id);
            expect($record->descendant_id)->toEqual((string) $child->id);
        });

        test('stores ancestor and descendant types correctly in database', function (): void {
            // Arrange
            $parent = User::query()->create(['name' => 'Parent', 'email' => 'parent@example.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'child@example.com']);

            // Act
            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);

            // Assert
            $record = DB::table('ancestors')
                ->where('depth', 1)
                ->first();

            expect($record->ancestor_type)->toBe($parent->getMorphClass());
            expect($record->descendant_type)->toBe($child->getMorphClass());
        });

        test('can add multiple levels of hierarchy', function (): void {
            // Arrange
            $grandparent = User::query()->create(['name' => 'Grandparent', 'email' => 'gp@example.com']);
            $parent = User::query()->create(['name' => 'Parent', 'email' => 'parent@example.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'child@example.com']);

            // Act
            Ancestry::addToAncestry($grandparent, 'seller');
            Ancestry::addToAncestry($parent, 'seller', $grandparent);
            Ancestry::addToAncestry($child, 'seller', $parent);

            // Assert
            $records = DB::table('ancestors')
                ->where('descendant_type', $child->getMorphClass())
                ->where('descendant_id', $child->id)
                ->where('type', 'seller')
                ->get();

            // Child should have self-reference (depth 0), parent reference (depth 1), grandparent reference (depth 2)
            expect($records)->toHaveCount(3);
        });

        test('can query ancestors efficiently', function (): void {
            // Arrange
            $root = User::query()->create(['name' => 'Root', 'email' => 'root@example.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'child@example.com']);

            Ancestry::addToAncestry($root, 'seller');
            Ancestry::addToAncestry($child, 'seller', $root);

            // Act
            $ancestors = Ancestry::getAncestors($child, 'seller');

            // Assert
            expect($ancestors)->toHaveCount(1);
            expect($ancestors->first()->id)->toBe($root->id);
        });

        test('can remove from hierarchy and verify database update', function (): void {
            // Arrange
            $parent = User::query()->create(['name' => 'Parent', 'email' => 'parent@example.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'child@example.com']);

            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);

            // Act
            Ancestry::removeFromAncestry($child, 'seller');

            // Assert
            $record = DB::table('ancestors')
                ->where('descendant_type', $child->getMorphClass())
                ->where('descendant_id', $child->id)
                ->where('type', 'seller')
                ->first();

            expect($record)->toBeNull();
            expect(Ancestry::isInAncestry($child, 'seller'))->toBeFalse();
        });

        test('handles depth correctly with morph context', function (): void {
            // Arrange
            $root = User::query()->create(['name' => 'Root', 'email' => 'root@example.com']);
            $mid = User::query()->create(['name' => 'Mid', 'email' => 'mid@example.com']);
            $leaf = User::query()->create(['name' => 'Leaf', 'email' => 'leaf@example.com']);

            // Act
            Ancestry::addToAncestry($root, 'seller');
            Ancestry::addToAncestry($mid, 'seller', $root);
            Ancestry::addToAncestry($leaf, 'seller', $mid);

            // Assert
            expect(Ancestry::getDepth($root, 'seller'))->toBe(0);
            expect(Ancestry::getDepth($mid, 'seller'))->toBe(1);
            expect(Ancestry::getDepth($leaf, 'seller'))->toBe(2);
        });

        test('different nodes can have different positions in hierarchy', function (): void {
            // Arrange
            $root = User::query()->create(['name' => 'Root', 'email' => 'root@example.com']);
            $child1 = User::query()->create(['name' => 'Child 1', 'email' => 'child1@example.com']);
            $child2 = User::query()->create(['name' => 'Child 2', 'email' => 'child2@example.com']);

            // Act
            Ancestry::addToAncestry($root, 'seller');
            Ancestry::addToAncestry($child1, 'seller', $root);
            Ancestry::addToAncestry($child2, 'seller', $root);

            // Assert
            expect(Ancestry::isDescendantOf($child1, $root, 'seller'))->toBeTrue();
            expect(Ancestry::isDescendantOf($child2, $root, 'seller'))->toBeTrue();
            expect(Ancestry::getDirectParent($child1, 'seller')->id)->toBe($root->id);
            expect(Ancestry::getDirectParent($child2, 'seller')->id)->toBe($root->id);
        });
    });

    describe('Custom Key Mapping', function (): void {
        test('uses mapped key name for ancestor/descendant id', function (): void {
            // Skip if using typed morphs (fixed-length id columns)
            if (in_array(config('ancestry.ancestor_morph_type', 'string'), ['numeric', 'uuid', 'ulid'], true)) {
                $this->markTestSkipped('Custom string keys only work with morph (varchar) configuration');
            }

            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'email', // Use email as the key instead of id
            ]);
            $parent = User::query()->create(['name' => 'Parent', 'email' => 'parent@custom.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'child@custom.com']);

            // Act
            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);

            // Assert
            $record = DB::table('ancestors')
                ->where('depth', 1)
                ->where('type', 'seller')
                ->first();

            expect($record)->not->toBeNull();
            expect($record->ancestor_id)->toBe('parent@custom.com');
            expect($record->descendant_id)->toBe('child@custom.com');
        });

        test('retrieves ancestors using mapped key', function (): void {
            // Skip if using typed morphs (fixed-length id columns)
            if (in_array(config('ancestry.ancestor_morph_type', 'string'), ['numeric', 'uuid', 'ulid'], true)) {
                $this->markTestSkipped('Custom string keys only work with morph (varchar) configuration');
            }

            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'email',
            ]);
            $parent = User::query()->create(['name' => 'Parent', 'email' => 'mapped-parent@example.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'mapped-child@example.com']);

            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);

            // Act
            $ancestors = Ancestry::getAncestors($child, 'seller');

            // Assert
            expect($ancestors)->toHaveCount(1);
            expect($ancestors->first()->email)->toBe('mapped-parent@example.com');
        });

        test('can query by custom key efficiently', function (): void {
            // Skip if using typed morphs (fixed-length id columns)
            if (in_array(config('ancestry.ancestor_morph_type', 'string'), ['numeric', 'uuid', 'ulid'], true)) {
                $this->markTestSkipped('Custom string keys only work with morph (varchar) configuration');
            }

            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'email',
            ]);
            $root = User::query()->create(['name' => 'Root', 'email' => 'root@custom.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'child@custom.com']);

            Ancestry::addToAncestry($root, 'seller');
            Ancestry::addToAncestry($child, 'seller', $root);

            // Act
            $record = DB::table('ancestors')
                ->where('type', 'seller')
                ->where('ancestor_type', $root->getMorphClass())
                ->where('ancestor_id', 'root@custom.com')
                ->where('depth', 1)
                ->first();

            // Assert
            expect($record)->not->toBeNull();
            expect($record->descendant_id)->toBe('child@custom.com');
        });

        test('isDescendantOf works with custom key mapping', function (): void {
            // Skip if using typed morphs (fixed-length id columns)
            if (in_array(config('ancestry.ancestor_morph_type', 'string'), ['numeric', 'uuid', 'ulid'], true)) {
                $this->markTestSkipped('Custom string keys only work with morph (varchar) configuration');
            }

            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'email',
            ]);
            $root = User::query()->create(['name' => 'Root', 'email' => 'root@check.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'child@check.com']);
            $unrelated = User::query()->create(['name' => 'Unrelated', 'email' => 'unrelated@check.com']);

            Ancestry::addToAncestry($root, 'seller');
            Ancestry::addToAncestry($child, 'seller', $root);
            Ancestry::addToAncestry($unrelated, 'seller');

            // Act & Assert
            expect(Ancestry::isDescendantOf($child, $root, 'seller'))->toBeTrue();
            expect(Ancestry::isDescendantOf($unrelated, $root, 'seller'))->toBeFalse();
        });
    });

    describe('Edge Cases', function (): void {
        test('handles id properly in morph id columns', function (): void {
            // Arrange
            $parent = User::query()->create(['name' => 'Parent', 'email' => 'parent@example.com']);
            $child = User::query()->create(['name' => 'Child', 'email' => 'child@example.com']);

            // Act
            Ancestry::addToAncestry($parent, 'seller');
            Ancestry::addToAncestry($child, 'seller', $parent);

            // Assert
            $record = DB::table('ancestors')->where('depth', 1)->first();
            // context_id can be int, uuid, or ulid depending on configuration
            expect($record->ancestor_id)->toBe($parent->id);
            expect($record->descendant_id)->toBe($child->id);
        });

        test('self-reference exists for all nodes', function (): void {
            // Arrange
            $user = User::query()->create(['name' => 'User', 'email' => 'user@example.com']);

            // Act
            Ancestry::addToAncestry($user, 'seller');

            // Assert
            $selfRef = DB::table('ancestors')
                ->where('ancestor_type', $user->getMorphClass())
                ->where('ancestor_id', $user->id)
                ->where('descendant_type', $user->getMorphClass())
                ->where('descendant_id', $user->id)
                ->where('depth', 0)
                ->where('type', 'seller')
                ->first();

            expect($selfRef)->not->toBeNull();
        });

        test('can check hierarchy across multiple nodes simultaneously', function (): void {
            // Arrange
            $root = User::query()->create(['name' => 'Root', 'email' => 'root@example.com']);
            $child1 = User::query()->create(['name' => 'Child 1', 'email' => 'child1@example.com']);
            $child2 = User::query()->create(['name' => 'Child 2', 'email' => 'child2@example.com']);
            $grandchild = User::query()->create(['name' => 'Grandchild', 'email' => 'grandchild@example.com']);

            Ancestry::addToAncestry($root, 'seller');
            Ancestry::addToAncestry($child1, 'seller', $root);
            Ancestry::addToAncestry($child2, 'seller', $root);
            Ancestry::addToAncestry($grandchild, 'seller', $child1);

            // Act & Assert
            expect(Ancestry::isDescendantOf($child1, $root, 'seller'))->toBeTrue();
            expect(Ancestry::isDescendantOf($child2, $root, 'seller'))->toBeTrue();
            expect(Ancestry::isDescendantOf($grandchild, $root, 'seller'))->toBeTrue();
            expect(Ancestry::isDescendantOf($grandchild, $child1, 'seller'))->toBeTrue();
            expect(Ancestry::isDescendantOf($grandchild, $child2, 'seller'))->toBeFalse();
        });
    });
});
