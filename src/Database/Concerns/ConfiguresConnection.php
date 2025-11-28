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
 * Trait for configuring model database connection from config.
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait ConfiguresConnection
{
    /**
     * Get the database connection for the model.
     *
     * Returns the configured connection from 'ancestry.connection',
     * falling back to the default database connection if not set.
     */
    #[Override()]
    public function getConnectionName(): ?string
    {
        /** @var null|string */
        return Config::get('ancestry.connection');
    }
}
