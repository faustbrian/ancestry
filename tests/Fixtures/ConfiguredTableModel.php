<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use Cline\Ancestry\Database\Concerns\ConfiguresTable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model using ConfiguresTable with its own table properties.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ConfiguredTableModel extends Model
{
    /** @use HasFactory<Factory<ConfiguredTableModel>> */
    use HasFactory;
    use ConfiguresTable;

    private string $configTableKey = 'custom_key';

    private string $defaultTable = 'custom_default';
}
