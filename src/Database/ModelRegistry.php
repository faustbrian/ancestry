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
 * This registry allows you to configure which primary key column should be used
 * for each model type in polymorphic relationships. This is particularly useful
 * when different models use different key types (e.g., User with 'uuid', Seller with 'ulid').
 *
 * Morph key functionality is delegated to Morpheus MorphKeyRegistry.
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
     * @param MorphKeyRegistry $morphKeyRegistry The shared morph key registry from Morpheus
     */
    public function __construct(
        private MorphKeyRegistry $morphKeyRegistry,
    ) {}

    /**
     * Register polymorphic key mappings.
     *
     * Delegates to Morpheus MorphKeyRegistry.
     *
     * @param array<class-string, string> $map Model class => column name mappings
     */
    public function morphKeyMap(array $map): void
    {
        $this->morphKeyRegistry->map($map);
    }

    /**
     * Register polymorphic key mappings and enforce their usage.
     *
     * Delegates to Morpheus MorphKeyRegistry.
     *
     * @param array<class-string, string> $map Model class => column name mappings
     */
    public function enforceMorphKeyMap(array $map): void
    {
        $this->morphKeyRegistry->enforce($map);
    }

    /**
     * Enable strict enforcement of key mappings.
     *
     * Delegates to Morpheus MorphKeyRegistry.
     */
    public function requireKeyMap(): void
    {
        $this->morphKeyRegistry->requireMapping();
    }

    /**
     * Get the polymorphic key column name for a model.
     *
     * Delegates to Morpheus MorphKeyRegistry.
     *
     * @param  Model  $model The model to get the key for
     * @return string The primary key column name
     */
    public function getModelKey(Model $model): string
    {
        return $this->morphKeyRegistry->getKey($model);
    }

    /**
     * Get the polymorphic key value for a model.
     *
     * Returns the value of the model's configured key column.
     *
     * @param  Model      $model The model to get the key value for
     * @return int|string The primary key value
     */
    public function getModelKeyValue(Model $model): int|string
    {
        $keyName = $this->getModelKey($model);

        /** @var int|string */
        return $model->getAttribute($keyName);
    }

    /**
     * Get the polymorphic key column name from a class string.
     *
     * Delegates to Morpheus MorphKeyRegistry.
     *
     * @param  class-string $class The model class
     * @return string       The primary key column name
     */
    public function getModelKeyFromClass(string $class): string
    {
        return $this->morphKeyRegistry->getKeyFromClass($class);
    }

    /**
     * Reset all registry state.
     *
     * Delegates to Morpheus MorphKeyRegistry.
     */
    public function reset(): void
    {
        $this->morphKeyRegistry->reset();
    }
}
