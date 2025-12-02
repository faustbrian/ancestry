<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Database\Concerns;

use Cline\Ancestry\Enums\PrimaryKeyType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Override;

use function class_uses_recursive;
use function in_array;
use function mb_strtolower;

/**
 * Trait for configuring model primary keys based on package configuration.
 *
 * Dynamically configures the model's primary key type based on the
 * 'ancestry.primary_key_type' configuration value. Supports auto-incrementing
 * integers, ULIDs, and UUIDs.
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait ConfiguresPrimaryKey
{
    /**
     * Initialize primary key configuration during model instantiation.
     *
     * Automatically configures the model's primary key properties (incrementing, keyType)
     * based on the configured primary key type from package configuration.
     */
    public function initializeConfiguresPrimaryKey(): void
    {
        $type = $this->getPrimaryKeyType();

        match ($type) {
            PrimaryKeyType::ULID => $this->configureUlidKey(),
            PrimaryKeyType::UUID => $this->configureUuidKey(),
            default => $this->configureIdKey(),
        };
    }

    /**
     * Get columns that should receive auto-generated unique identifiers.
     *
     * Returns the primary key column name when using ULID or UUID types,
     * enabling Laravel's unique ID generation features. Returns an empty
     * array for auto-incrementing integer keys.
     *
     * @return array<int, string> Column names requiring unique ID generation
     */
    #[Override()]
    public function uniqueIds(): array
    {
        $type = $this->getPrimaryKeyType();

        if ($type === PrimaryKeyType::ULID || $type === PrimaryKeyType::UUID) {
            return [$this->getKeyName()];
        }

        return [];
    }

    /**
     * Generate a new unique identifier value for the model.
     *
     * Creates a new ULID or UUID based on the configured primary key type.
     * Returns null for auto-incrementing integer keys. ULIDs are converted
     * to lowercase for consistency.
     *
     * @return null|string The generated unique ID, or null for integer keys
     */
    public function newUniqueId(): ?string
    {
        $type = $this->getPrimaryKeyType();

        return match ($type) {
            PrimaryKeyType::ULID => mb_strtolower((string) Str::ulid()),
            PrimaryKeyType::UUID => (string) Str::uuid(),
            default => null,
        };
    }

    /**
     * Boot the trait and register model event listeners.
     *
     * Registers a model 'creating' event listener that automatically generates
     * and assigns ULID or UUID primary key values when the model is being created,
     * unless a value has already been manually set.
     */
    protected static function bootConfiguresPrimaryKey(): void
    {
        static::creating(function (Model $model): void {
            /** @var int|string $configValue */
            $configValue = Config::get('ancestry.primary_key_type', 'id');
            $primaryKeyType = PrimaryKeyType::tryFrom($configValue) ?? PrimaryKeyType::Id;

            // Skip auto-generation for standard auto-incrementing IDs
            if ($primaryKeyType === PrimaryKeyType::Id) {
                return;
            }

            $keyName = $model->getKeyName();
            $existingValue = $model->getAttribute($keyName);

            // Auto-generate if no value was manually set
            if (!$existingValue) {
                $value = match ($primaryKeyType) {
                    PrimaryKeyType::ULID => mb_strtolower((string) Str::ulid()),
                    PrimaryKeyType::UUID => (string) Str::uuid(),
                };

                $model->setAttribute($keyName, $value);
            }
        });
    }

    /**
     * Get the configured primary key type from package configuration.
     *
     * Retrieves the primary key type setting from 'ancestry.primary_key_type',
     * defaulting to 'id' (auto-incrementing integer) if not configured.
     *
     * @return PrimaryKeyType The configured primary key type enum
     */
    protected function getPrimaryKeyType(): PrimaryKeyType
    {
        /** @var int|string $configValue */
        $configValue = Config::get('ancestry.primary_key_type', 'id');

        return PrimaryKeyType::tryFrom($configValue) ?? PrimaryKeyType::Id;
    }

    /**
     * Configure model for auto-incrementing integer primary key.
     *
     * Sets the model's incrementing property to true and keyType to 'int',
     * enabling standard database auto-increment behavior.
     */
    protected function configureIdKey(): void
    {
        $this->incrementing = true;
        $this->keyType = 'int';
    }

    /**
     * Configure model for ULID primary key.
     *
     * Sets the model's incrementing property to false and keyType to 'string',
     * enabling ULID-based primary keys. Compatible with Laravel's HasUlids trait
     * if present on the model.
     */
    protected function configureUlidKey(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';

        // Use HasUlids trait methods if available
        if (in_array(HasUlids::class, class_uses_recursive(static::class), true)) {
        }
    }

    /**
     * Configure model for UUID primary key.
     *
     * Sets the model's incrementing property to false and keyType to 'string',
     * enabling UUID-based primary keys. Compatible with Laravel's HasUuids trait
     * if present on the model.
     */
    protected function configureUuidKey(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';

        // Use HasUuids trait methods if available
        if (in_array(HasUuids::class, class_uses_recursive(static::class), true)) {
        }
    }
}
