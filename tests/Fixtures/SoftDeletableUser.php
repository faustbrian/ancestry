<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use Cline\Ancestry\Concerns\HasAncestry;
use Cline\VariableKeys\Database\Concerns\HasVariablePrimaryKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Override;

/**
 * Test user model that soft deletes.
 *
 * @property null|Carbon $created_at
 * @property null|Carbon $deleted_at
 * @property int|string  $id
 * @property null|string $name
 * @property null|Carbon $updated_at
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class SoftDeletableUser extends Model
{
    /** @use HasFactory<Factory<SoftDeletableUser>> */
    use HasFactory;
    use HasVariablePrimaryKey;
    use HasAncestry;
    use SoftDeletes;

    #[Override()]
    protected $table = 'soft_deletable_users';

    #[Override()]
    protected $guarded = [];
}
