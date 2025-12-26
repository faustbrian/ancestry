<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Database;

use Carbon\Carbon;
use Cline\Ancestry\Contracts\AncestryType;
use Cline\Ancestry\Database\Concerns\ConfiguresConnection;
use Cline\VariableKeys\Database\Concerns\HasVariablePrimaryKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;
use Override;

/**
 * Point-in-time snapshot of a hierarchy chain for a given context.
 *
 * Example: A shipment is created for customer "Acme Corp" who has seller "Sales Partner A"
 * assigned. "Sales Partner A" has parent seller "Regional Manager B" who has parent "VP Sales C".
 *
 * The snapshot captures the seller hierarchy at shipment creation:
 *   - depth 0: Sales Partner A (direct seller for customer)
 *   - depth 1: Regional Manager B (parent of Sales Partner A)
 *   - depth 2: VP Sales C (grandparent, root of chain)
 *
 * This preserves the commission chain even if hierarchies change later.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @property string $ancestor_id
 * @property Model  $context
 * @property string $context_id
 * @property string $context_type
 * @property Carbon $created_at
 * @property int    $depth
 * @property string $id
 * @property string $type
 * @property Carbon $updated_at
 */
final class AncestorSnapshot extends Model
{
    /** @use HasFactory<Factory<AncestorSnapshot>> */
    use HasFactory;
    use ConfiguresConnection;
    use HasVariablePrimaryKey;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'context_type',
        'context_id',
        'type',
        'depth',
        'ancestor_id',
    ];

    /**
     * Get the database table name for ancestor snapshots.
     *
     * Retrieves the table name from the 'ancestry.snapshots.table_name' configuration,
     * defaulting to 'hierarchy_snapshots' if not configured.
     *
     * @return string The table name for snapshot storage
     */
    #[Override()]
    public function getTable(): string
    {
        /** @var string */
        return Config::get('ancestry.snapshots.table_name', 'hierarchy_snapshots');
    }

    /**
     * Get the polymorphic context model that owns this snapshot.
     *
     * The context represents the entity for which this hierarchy snapshot was captured
     * (e.g., a shipment, order, or commission record). This allows tracking which
     * business entity triggered the snapshot creation.
     *
     * @return MorphTo<Model, $this>
     */
    public function context(): MorphTo
    {
        return $this->morphTo('context');
    }

    /**
     * Get the attribute casting configuration for the model.
     *
     * Ensures the depth column is properly cast to an integer for type-safe
     * operations and comparisons.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'depth' => 'integer',
        ];
    }

    /**
     * Scope query to snapshots for a specific context model.
     *
     * Filters snapshots to only those associated with the provided context model
     * by matching both the morph type and ID.
     *
     * @param  Builder<self> $query   The query builder instance
     * @param  Model         $context The context model to filter by
     * @return Builder<self> The scoped query builder
     */
    #[Scope()]
    protected function forContext($query, Model $context)
    {
        return $query->where('context_type', $context->getMorphClass())
            ->where('context_id', $context->getKey());
    }

    /**
     * Scope query to snapshots of a specific hierarchy type.
     *
     * Filters snapshots to only those matching the specified hierarchy type
     * (e.g., 'seller', 'organization'). Accepts either an AncestryType enum
     * or a string value.
     *
     * @param  Builder<self>       $query The query builder instance
     * @param  AncestryType|string $type  The hierarchy type to filter by
     * @return Builder<self>       The scoped query builder
     */
    #[Scope()]
    protected function ofType($query, AncestryType|string $type)
    {
        $typeValue = $type instanceof AncestryType ? $type->value() : $type;

        return $query->where('type', $typeValue);
    }

    /**
     * Scope query to order snapshots by depth in ascending order.
     *
     * Orders snapshots from lowest depth (closest to root) to highest depth
     * (furthest from root), useful for traversing hierarchy chains from
     * top to bottom.
     *
     * @param  Builder<self> $query The query builder instance
     * @return Builder<self> The ordered query builder
     */
    #[Scope()]
    protected function orderedByDepth($query): Builder
    {
        /** @var Builder<self> */
        return $query->orderBy('depth');
    }
}
