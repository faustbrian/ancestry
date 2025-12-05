<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use Cline\Ancestry\Concerns\HasAncestrySnapshots;
use Cline\Ancestry\Database\Concerns\ConfiguresPrimaryKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Test order model for snapshot testing.
 *
 * @property null|Carbon $created_at
 * @property int|string  $id
 * @property null|string $reference
 * @property null|Carbon $updated_at
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Order extends Model
{
    use ConfiguresPrimaryKey;

    /** @use HasFactory<Factory<Order>> */
    use HasFactory;
    use HasAncestrySnapshots;

    protected $table = 'orders';

    protected $guarded = [];
}
