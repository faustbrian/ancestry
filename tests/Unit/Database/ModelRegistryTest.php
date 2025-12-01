<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Database\ModelRegistry;
use Cline\Morpheus\Exceptions\MorphKeyViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->registry = resolve(ModelRegistry::class);
    $this->registry->reset();
});

afterEach(function (): void {
    $this->registry->reset();
});

describe('ModelRegistry', function (): void {
    describe('Happy Paths', function (): void {
        test('returns model key name when no mapping exists', function (): void {
            // Arrange
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act
            $result = $this->registry->getModelKey($user);

            // Assert
            expect($result)->toBe('id');
        });

        test('returns mapped key when mapping exists', function (): void {
            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'uuid',
            ]);
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act
            $result = $this->registry->getModelKey($user);

            // Assert
            expect($result)->toBe('uuid');
        });

        test('allows multiple models to be mapped simultaneously', function (): void {
            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'id',
                'App\\Models\\Seller' => 'ulid',
            ]);
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act & Assert
            expect($this->registry->getModelKey($user))->toBe('id');
            expect($this->registry->getModelKeyFromClass('App\\Models\\Seller'))->toBe('ulid');
        });

        test('merges multiple morph key map calls', function (): void {
            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'id',
            ]);
            $this->registry->morphKeyMap([
                'App\\Models\\Seller' => 'ulid',
            ]);
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act & Assert
            expect($this->registry->getModelKey($user))->toBe('id');
            expect($this->registry->getModelKeyFromClass('App\\Models\\Seller'))->toBe('ulid');
        });

        test('does not throw without enforcement when mapping missing', function (): void {
            // Arrange
            $this->registry->morphKeyMap([
                'App\\Models\\Seller' => 'ulid',
            ]);
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act
            $key = $this->registry->getModelKey($user);

            // Assert - Falls back to model's actual configured key name
            expect($key)->toBe($user->getKeyName());
        });

        test('does not throw with enforcement when mapping exists', function (): void {
            // Arrange
            $this->registry->enforceMorphKeyMap([
                User::class => 'id',
                'App\\Models\\Seller' => 'ulid',
            ]);
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act
            $key = $this->registry->getModelKey($user);

            // Assert
            expect($key)->toBe('id');
        });

        test('getModelKeyValue returns the correct value for mapped key', function (): void {
            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'email',
            ]);
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act
            $value = $this->registry->getModelKeyValue($user);

            // Assert
            expect($value)->toBe('john@example.com');
        });

        test('getModelKeyValue returns id value when no mapping', function (): void {
            // Arrange
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act
            $value = $this->registry->getModelKeyValue($user);

            // Assert
            expect($value)->toBe($user->id);
        });
    });

    describe('Sad Paths', function (): void {
        test('throws with enforcement when mapping missing', function (): void {
            // Arrange
            $this->expectException(MorphKeyViolationException::class);
            $this->expectExceptionMessage('Model [Tests\Fixtures\User] is not mapped in the morph key registry');
            $this->registry->enforceMorphKeyMap([
                'App\\Models\\Seller' => 'ulid',
            ]);
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act & Assert (expect exception)
            $this->registry->getModelKey($user);
        });

        test('throws when require key map enabled and mapping missing', function (): void {
            // Arrange
            $this->expectException(MorphKeyViolationException::class);
            $this->registry->morphKeyMap([
                'App\\Models\\Seller' => 'ulid',
            ]);
            $this->registry->requireKeyMap();

            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);

            // Act & Assert (expect exception)
            $this->registry->getModelKey($user);
        });
    });

    describe('Edge Cases', function (): void {
        test('singleton attribute creates singleton instance in container', function (): void {
            // Arrange
            $app = app();

            // Act
            $instance1 = $app->make(ModelRegistry::class);
            $instance2 = $app->make(ModelRegistry::class);

            // Assert - Should return the same instance (singleton behavior via #[Singleton] attribute)
            expect($instance1)->toBe($instance2)
                ->and($instance1)->toBeInstanceOf(ModelRegistry::class);
        });

        test('reset clears key mappings', function (): void {
            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'uuid',
                'App\\Models\\Seller' => 'ulid',
            ]);

            // Act
            $this->registry->reset();

            // Assert
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);
            expect($this->registry->getModelKey($user))->toBe('id');
        });

        test('reset clears enforcement flag', function (): void {
            // Arrange
            $this->registry->enforceMorphKeyMap([
                User::class => 'id',
            ]);

            // Act
            $this->registry->reset();

            // Assert - After reset, doesn't throw and falls back to model's key
            $user = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);
            $key = $this->registry->getModelKey($user);
            expect($key)->toBe($user->getKeyName());
        });

        test('getModelKeyFromClass works with mapped class', function (): void {
            // Arrange
            $this->registry->morphKeyMap([
                User::class => 'uuid',
            ]);

            // Act
            $key = $this->registry->getModelKeyFromClass(User::class);

            // Assert
            expect($key)->toBe('uuid');
        });

        test('getModelKeyFromClass falls back to instantiation without mapping', function (): void {
            // Act
            $key = $this->registry->getModelKeyFromClass(User::class);

            // Assert
            expect($key)->toBe('id'); // Default key name for User model
        });

        test('getModelKeyFromClass throws with enforcement when mapping missing', function (): void {
            // Arrange
            $this->expectException(MorphKeyViolationException::class);
            $this->expectExceptionMessage('Model [Tests\Fixtures\User] is not mapped in the morph key registry');
            $this->registry->enforceMorphKeyMap([
                'App\\Models\\Seller' => 'ulid',
            ]);

            // Act & Assert (expect exception)
            $this->registry->getModelKeyFromClass(User::class);
        });
    });
});
