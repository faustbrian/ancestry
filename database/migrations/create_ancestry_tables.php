<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Enums\MorphType;
use Cline\Ancestry\Enums\PrimaryKeyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the ancestors table using the closure table pattern for efficient
     * hierarchical queries. Each row represents a path between an ancestor and
     * descendant with the depth of the relationship.
     *
     * Also creates the ancestor_snapshots table for capturing point-in-time
     * ancestry chains, enabling preservation of historical relationships.
     */
    public function up(): void
    {
        $this->createAncestorsTable();
        $this->createAncestorSnapshotsTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Config::get('ancestry.connection');
        $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();

        $schema->dropIfExists(Config::get('ancestry.snapshots.table_name', 'ancestor_snapshots'));
        $schema->dropIfExists(Config::get('ancestry.table_name', 'ancestors'));
    }

    /**
     * Create the ancestors table.
     */
    private function createAncestorsTable(): void
    {
        $tableName = Config::get('ancestry.table_name', 'ancestors');
        $connection = Config::get('ancestry.connection');
        $primaryKeyType = PrimaryKeyType::tryFrom(Config::get('ancestry.primary_key_type', 'id')) ?? PrimaryKeyType::Id;
        $ancestorMorphType = MorphType::tryFrom(Config::get('ancestry.ancestor_morph_type', 'morph')) ?? MorphType::Morph;
        $descendantMorphType = MorphType::tryFrom(Config::get('ancestry.descendant_morph_type', 'morph')) ?? MorphType::Morph;

        $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();

        $schema->create($tableName, function (Blueprint $table) use ($primaryKeyType, $ancestorMorphType, $descendantMorphType): void {
            // Primary key based on configuration
            match ($primaryKeyType) {
                PrimaryKeyType::ULID => $table->ulid('id')->primary(),
                PrimaryKeyType::UUID => $table->uuid('id')->primary(),
                default => $table->id(),
            };

            // Ancestor polymorphic relationship
            match ($ancestorMorphType) {
                MorphType::UUIDMorph => $table->uuidMorphs('ancestor'),
                MorphType::ULIDMorph => $table->ulidMorphs('ancestor'),
                default => $table->morphs('ancestor'),
            };

            // Descendant polymorphic relationship
            match ($descendantMorphType) {
                MorphType::UUIDMorph => $table->uuidMorphs('descendant'),
                MorphType::ULIDMorph => $table->ulidMorphs('descendant'),
                default => $table->morphs('descendant'),
            };

            // Depth of the relationship (0 = self-reference, 1 = direct parent/child, etc.)
            $table->unsignedSmallInteger('depth');

            // Ancestry type (e.g., 'seller', 'reseller', 'organization')
            $table->string('type', 50)->index();

            $table->timestamps();

            // Composite unique constraint to prevent duplicate paths
            $table->unique(
                ['ancestor_type', 'ancestor_id', 'descendant_type', 'descendant_id', 'type'],
                'ancestors_path_unique',
            );

            // Indexes for common queries
            $table->index(['descendant_type', 'descendant_id', 'type'], 'ancestors_descendant_type');
            $table->index(['ancestor_type', 'ancestor_id', 'type'], 'ancestors_ancestor_type');
            $table->index(['type', 'depth'], 'ancestors_type_depth');
        });
    }

    /**
     * Create the ancestor_snapshots table.
     */
    private function createAncestorSnapshotsTable(): void
    {
        $tableName = Config::get('ancestry.snapshots.table_name', 'ancestor_snapshots');
        $connection = Config::get('ancestry.connection');
        $primaryKeyType = PrimaryKeyType::tryFrom(Config::get('ancestry.primary_key_type', 'id')) ?? PrimaryKeyType::Id;
        $contextMorphType = MorphType::tryFrom(Config::get('ancestry.snapshots.context_morph_type', 'morph')) ?? MorphType::Morph;
        $ancestorKeyType = Config::get('ancestry.snapshots.ancestor_key_type', 'ulid');

        $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();

        $schema->create($tableName, function (Blueprint $table) use ($primaryKeyType, $contextMorphType, $ancestorKeyType): void {
            // Primary key based on configuration
            match ($primaryKeyType) {
                PrimaryKeyType::ULID => $table->ulid('id')->primary(),
                PrimaryKeyType::UUID => $table->uuid('id')->primary(),
                default => $table->id(),
            };

            // Context polymorphic relationship (what the snapshot is for)
            match ($contextMorphType) {
                MorphType::UUIDMorph => $table->uuidMorphs('context'),
                MorphType::ULIDMorph => $table->ulidMorphs('context'),
                default => $table->morphs('context'),
            };

            // Ancestry type (e.g., 'seller', 'reseller', 'organization')
            $table->string('type', 50)->index();

            // Depth in the ancestry chain (0 = direct, 1 = parent, 2 = grandparent, etc.)
            $table->unsignedSmallInteger('depth');

            // Ancestor at this depth level
            match ($ancestorKeyType) {
                'uuid' => $table->uuid('ancestor_id'),
                'ulid' => $table->ulid('ancestor_id'),
                default => $table->unsignedBigInteger('ancestor_id'),
            };

            $table->timestamps();

            // Composite unique constraint - one snapshot per context + type + depth
            $table->unique(
                ['context_type', 'context_id', 'type', 'depth'],
                'ancestor_snapshots_unique',
            );

            // Indexes for common queries
            $table->index(['context_type', 'context_id', 'type'], 'ancestor_snapshots_context_type');
            $table->index(['ancestor_id', 'type'], 'ancestor_snapshots_ancestor_type');
        });
    }
};
