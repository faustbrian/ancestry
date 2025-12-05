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

/**
 * Configures model database connection from package configuration.
 *
 * This trait enables models to use a custom database connection specified
 * in the 'ancestry.connection' configuration, allowing the package to operate
 * on a different database than the application's default.
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait ConfiguresConnection
{
    /**
     * Get the database connection name for the model.
     *
     * Retrieves the connection name from the 'ancestry.connection' configuration.
     * Returns null if not configured, allowing the model to use Laravel's default
     * database connection.
     *
     * @return null|string The connection name, or null to use the default connection
     */
    #[Override()]
    public function getConnectionName(): ?string
    {
        /** @var null|string */
        return Config::get('ancestry.connection');
    }
}
