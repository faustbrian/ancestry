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
     * Initialize the trait and configure primary key settings.
     */
    public function initializeConfiguresPrimaryKey(): void
    {
        $type = $this->getPrimaryKeyType();

        match ($type) {
            PrimaryKeyType::Ulid => $this->configureUlidKey(),
            PrimaryKeyType::Uuid => $this->configureUuidKey(),
            default => $this->configureIdKey(),
        };
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    #[Override()]
    public function uniqueIds(): array
    {
        $type = $this->getPrimaryKeyType();

        if ($type === PrimaryKeyType::Ulid || $type === PrimaryKeyType::Uuid) {
            return [$this->getKeyName()];
        }

        return [];
    }

    /**
     * Generate a new unique identifier for the model.
     */
    public function newUniqueId(): ?string
    {
        $type = $this->getPrimaryKeyType();

        return match ($type) {
            PrimaryKeyType::Ulid => mb_strtolower((string) Str::ulid()),
            PrimaryKeyType::Uuid => (string) Str::uuid(),
            default => null,
        };
    }

    /**
     * Boot the trait and register model event listeners.
     *
     * Automatically generates and assigns primary key values during model creation
     * when using UUID or ULID.
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
                    PrimaryKeyType::Ulid => mb_strtolower((string) Str::ulid()),
                    PrimaryKeyType::Uuid => (string) Str::uuid(),
                };

                $model->setAttribute($keyName, $value);
            }
        });
    }

    /**
     * Get the configured primary key type.
     */
    protected function getPrimaryKeyType(): PrimaryKeyType
    {
        /** @var int|string $configValue */
        $configValue = Config::get('ancestry.primary_key_type', 'id');

        return PrimaryKeyType::tryFrom($configValue) ?? PrimaryKeyType::Id;
    }

    /**
     * Configure for auto-incrementing integer primary key.
     */
    protected function configureIdKey(): void
    {
        $this->incrementing = true;
        $this->keyType = 'int';
    }

    /**
     * Configure for ULID primary key.
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
     * Configure for UUID primary key.
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
