<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Database;

use Cline\Ancestry\Database\Concerns\ConfiguresConnection;
use Cline\Ancestry\Database\Concerns\ConfiguresPrimaryKey;
use Cline\Ancestry\Database\Concerns\ConfiguresTable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

use function app;

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
    use ConfiguresPrimaryKey;
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
     * Get the ancestor model.
     *
     * Uses ModelRegistry to resolve the model with custom key mapping support.
     * When a custom key is configured (e.g., 'email' instead of 'id'), this
     * method queries the model using the appropriate key column.
     */
    public function ancestor(): ?Model
    {
        return $this->resolveModel($this->ancestor_type, $this->ancestor_id);
    }

    /**
     * Get the descendant model.
     *
     * Uses ModelRegistry to resolve the model with custom key mapping support.
     * When a custom key is configured (e.g., 'email' instead of 'id'), this
     * method queries the model using the appropriate key column.
     */
    public function descendant(): ?Model
    {
        return $this->resolveModel($this->descendant_type, $this->descendant_id);
    }

    /**
     * Get the ancestor relationship for eager loading.
     *
     * @return MorphTo<Model, $this>
     */
    public function ancestorRelation(): MorphTo
    {
        return $this->morphTo('ancestor');
    }

    /**
     * Get the descendant relationship for eager loading.
     *
     * @return MorphTo<Model, $this>
     */
    public function descendantRelation(): MorphTo
    {
        return $this->morphTo('descendant');
    }

    /**
     * Resolve a model using the custom key mapping from ModelRegistry.
     *
     * @param string $type The morph class name
     * @param mixed  $id   The morph id value
     */
    private function resolveModel(string $type, mixed $id): ?Model
    {
        /** @var class-string<Model> $class */
        $class = $type;

        /** @var ModelRegistry $registry */
        $registry = app(ModelRegistry::class);
        $keyColumn = $registry->getModelKeyFromClass($class);

        return $class::query()->where($keyColumn, $id)->first();
    }
}
