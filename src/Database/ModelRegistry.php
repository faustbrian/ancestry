<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Database;

use Cline\Morpheus\MorphKeyRegistry;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Model;

/**
 * Registry for managing polymorphic relationship key mappings.
 *
 * This registry enables configuration of custom primary key columns for polymorphic
 * relationships, essential when different models use different key types (e.g., User
 * with 'uuid', Seller with 'ulid'). All morph key functionality is delegated to the
 * Morpheus package's MorphKeyRegistry.
 *
 * ```php
 * // Register custom key mappings
 * $registry->morphKeyMap([
 *     User::class => 'uuid',
 *     Seller::class => 'ulid',
 * ]);
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
#[Singleton()]
final readonly class ModelRegistry
{
    /**
     * Create a new ModelRegistry instance.
     *
     * @param MorphKeyRegistry $morphKeyRegistry The Morpheus morph key registry dependency
     *                                           that handles the actual key mapping logic
     */
    public function __construct(
        private MorphKeyRegistry $morphKeyRegistry,
    ) {}

    /**
     * Register polymorphic key mappings for models.
     *
     * Maps model classes to their primary key column names. This allows polymorphic
     * relationships to use custom keys instead of assuming 'id'. Delegates to Morpheus
     * MorphKeyRegistry for the actual registration logic.
     *
     * @param array<class-string, string> $map Model class => column name mappings
     *                                         (e.g., [User::class => 'uuid', Seller::class => 'ulid'])
     */
    public function morphKeyMap(array $map): void
    {
        $this->morphKeyRegistry->map($map);
    }

    /**
     * Register polymorphic key mappings with strict enforcement.
     *
     * Maps model classes to their primary key columns and enforces that all models
     * must have a registered mapping. Any unmapped model will trigger an error.
     * Useful for ensuring consistent key usage across the application.
     *
     * @param array<class-string, string> $map Model class => column name mappings
     *                                         (e.g., [User::class => 'uuid', Seller::class => 'ulid'])
     */
    public function enforceMorphKeyMap(array $map): void
    {
        $this->morphKeyRegistry->enforce($map);
    }

    /**
     * Enable strict enforcement mode for all key mappings.
     *
     * Requires that all models used in polymorphic relationships have registered
     * key mappings. This prevents accidental usage of unmapped models and ensures
     * explicit key configuration throughout the application.
     */
    public function requireKeyMap(): void
    {
        $this->morphKeyRegistry->requireMapping();
    }

    /**
     * Get the primary key column name for a model instance.
     *
     * Retrieves the registered key column name for the given model. Falls back to
     * the model's getKeyName() if no custom mapping is registered.
     *
     * @param  Model  $model The model instance to get the key column for
     * @return string The primary key column name (e.g., 'id', 'uuid', 'email')
     */
    public function getModelKey(Model $model): string
    {
        return $this->morphKeyRegistry->getKey($model);
    }

    /**
     * Get the primary key value for a model instance.
     *
     * Retrieves the actual value of the model's configured primary key column.
     * This is the value stored in the polymorphic relationship, which may differ
     * from getKey() if a custom key mapping is configured.
     *
     * @param  Model      $model The model instance to get the key value from
     * @return int|string The primary key value (e.g., 123, 'uuid-string', 'user@example.com')
     */
    public function getModelKeyValue(Model $model): int|string
    {
        $keyName = $this->getModelKey($model);

        /** @var int|string */
        return $model->getAttribute($keyName);
    }

    /**
     * Get the primary key column name from a model class string.
     *
     * Retrieves the registered key column name for the given model class.
     * Useful when you have the class name but not an instance.
     *
     * @param  class-string $class The fully qualified model class name
     * @return string       The primary key column name (e.g., 'id', 'uuid', 'email')
     */
    public function getModelKeyFromClass(string $class): string
    {
        return $this->morphKeyRegistry->getKeyFromClass($class);
    }

    /**
     * Reset all registry mappings and enforcement settings.
     *
     * Clears all registered key mappings and disables enforcement mode.
     * Primarily useful for testing scenarios where you need to reset
     * the registry state between tests.
     */
    public function reset(): void
    {
        $this->morphKeyRegistry->reset();
    }

    /**
     * Resolve a morph alias to its fully qualified class name.
     *
     * Resolves short morph aliases (e.g., 'Account') to their fully qualified
     * class names (e.g., 'Domain\Accounting\Models\Account') using Laravel's
     * morph map. Returns the original input if already a valid class or if
     * no morph map entry exists.
     *
     * @param  string $class Class name or morph alias to resolve
     * @return string Fully qualified class name
     */
    public function resolveMorphAlias(string $class): string
    {
        return $this->morphKeyRegistry->resolveMorphAlias($class);
    }
}
