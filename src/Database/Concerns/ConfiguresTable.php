<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Database\Concerns;

use Illuminate\Support\Facades\Config;
use Override;

use function sprintf;

/**
 * Trait for configuring model table names from config.
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait ConfiguresTable
{
    /**
     * Get the table associated with the model.
     *
     * Resolves the table name from configuration, falling back to the
     * default table name if not configured.
     */
    #[Override()]
    public function getTable(): string
    {
        $configKey = $this->configTableKey ?? 'table_name';
        $defaultTable = $this->defaultTable ?? 'hierarchies';

        /** @var string */
        return Config::get(sprintf('ancestry.%s', $configKey), $defaultTable);
    }
}
