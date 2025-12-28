<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Database;

use Cline\Ancestry\Database\Concerns\ConfiguresConnection;
use Cline\Ancestry\Database\Concerns\ConfiguresTable;
use Cline\VariableKeys\Database\Concerns\HasVariablePrimaryKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

use function resolve;

/**
 * Eloquent model for hierarchy entries in the closure table.
 *
 * Each row represents a path between an ancestor and descendant, with the depth
 * indicating the number of generations between them. A depth of 0 means the
 * ancestor and descendant are the same model (self-reference).
 *
 * @property string      $ancestor_id     The primary key of the ancestor
 * @property string      $ancestor_type   The morph class of the ancestor
 * @property null|Carbon $created_at
 * @property int         $depth           The depth of the relationship (0 = self, 1 = direct parent/child, etc.)
 * @property string      $descendant_id   The primary key of the descendant
 * @property string      $descendant_type The morph class of the descendant
 * @property string      $type            The hierarchy type identifier
 * @property null|Carbon $updated_at
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Ancestor extends Model
{
    /** @use HasFactory<Factory<Ancestor>> */
    use HasFactory;
    use ConfiguresConnection;
    use HasVariablePrimaryKey;
    use ConfiguresTable;

    /**
     * The config key for table name resolution.
     */
    protected string $configTableKey = 'table_name';

    /**
     * The default table name if not configured.
     */
    protected string $defaultTable = 'hierarchies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ancestor_type',
        'ancestor_id',
        'descendant_type',
        'descendant_id',
        'depth',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'depth' => 'integer',
    ];

    /**
     * Get the ancestor model instance.
     *
     * Resolves the ancestor model using ModelRegistry to support custom key mappings.
     * When a custom key is configured (e.g., 'email' instead of 'id'), this method
     * queries the model using the appropriate key column.
     *
     * @return null|Model The resolved ancestor model, or null if not found
     */
    public function ancestor(): ?Model
    {
        return $this->resolveModel($this->ancestor_type, $this->ancestor_id);
    }

    /**
     * Get the descendant model instance.
     *
     * Resolves the descendant model using ModelRegistry to support custom key mappings.
     * When a custom key is configured (e.g., 'email' instead of 'id'), this method
     * queries the model using the appropriate key column.
     *
     * @return null|Model The resolved descendant model, or null if not found
     */
    public function descendant(): ?Model
    {
        return $this->resolveModel($this->descendant_type, $this->descendant_id);
    }

    /**
     * Get the polymorphic ancestor relationship for eager loading.
     *
     * Defines a morphTo relationship for the ancestor, enabling efficient eager loading
     * of ancestor models through Eloquent's relationship system.
     *
     * @return MorphTo<Model, $this>
     */
    public function ancestorRelation(): MorphTo
    {
        return $this->morphTo('ancestor');
    }

    /**
     * Get the polymorphic descendant relationship for eager loading.
     *
     * Defines a morphTo relationship for the descendant, enabling efficient eager loading
     * of descendant models through Eloquent's relationship system.
     *
     * @return MorphTo<Model, $this>
     */
    public function descendantRelation(): MorphTo
    {
        return $this->morphTo('descendant');
    }

    /**
     * Resolve a model instance using custom key mapping from ModelRegistry.
     *
     * Queries the model using the configured primary key column (e.g., 'id', 'uuid', 'email')
     * as defined in the ModelRegistry. This enables support for non-standard primary keys
     * in polymorphic relationships. Resolves morph aliases to fully qualified class names.
     *
     * @param  string     $type The morph class name or alias
     * @param  mixed      $id   The primary key value to search for
     * @return null|Model The resolved model instance, or null if not found
     */
    private function resolveModel(string $type, mixed $id): ?Model
    {
        /** @var ModelRegistry $registry */
        $registry = resolve(ModelRegistry::class);

        /** @var class-string<Model> $class */
        $class = $registry->resolveMorphAlias($type);

        $keyColumn = $registry->getModelKeyFromClass($class);

        return $class::query()->where($keyColumn, $id)->first();
    }
}
