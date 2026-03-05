<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ancestry\Exceptions;

use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use RuntimeException;

/**
 * Base exception for invalid configuration errors.
 *
 * This abstract exception serves as the parent for all configuration-related
 * exceptions in the ancestry package, allowing consumers to catch all
 * configuration errors with a single catch block if desired.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class InvalidConfigurationException extends RuntimeException implements AncestryException, ProvidesSolution
{
    // Abstract base - no factory methods

    public function getSolution(): Solution
    {
        /** @var BaseSolution $solution */
        $solution = BaseSolution::create('Review package usage and configuration.');

        return $solution
            ->setSolutionDescription('Exception: '.$this->getMessage())
            ->setDocumentationLinks([
                'Package documentation' => 'https://github.com/cline/ancestry',
            ]);
    }
}
