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
 * Configures model table names from package configuration.
 *
 * This trait enables dynamic table name resolution from the ancestry configuration,
 * allowing customization of table names through config files rather than hardcoding.
 * Models using this trait should define $configTableKey and $defaultTable properties.
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait ConfiguresTable
{
    /**
     * Get the database table name for the model.
     *
     * Resolves the table name from the ancestry configuration using the model's
     * $configTableKey property (e.g., 'table_name'). Falls back to $defaultTable
     * if not configured. This enables centralized table name configuration.
     *
     * @return string The resolved table name
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
