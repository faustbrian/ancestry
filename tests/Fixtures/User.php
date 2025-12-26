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
use Illuminate\Support\Carbon;

/**
 * Test user model.
 *
 * @property null|Carbon $created_at
 * @property null|string $email
 * @property int|string  $id
 * @property null|string $name
 * @property null|Carbon $updated_at
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class User extends Model
{
    use HasVariablePrimaryKey;

    /** @use HasFactory<Factory<User>> */
    use HasFactory;
    use HasAncestry;

    protected $table = 'users';

    protected $guarded = [];
}
