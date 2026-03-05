<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Exceptions\InvalidConfigurationException;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;

it('provides an ignition solution', function (): void {
    $exception = new class() extends InvalidConfigurationException
    {
        public function __construct() {}
    };

    expect($exception)->toBeInstanceOf(ProvidesSolution::class);

    $solution = $exception->getSolution();

    expect($solution)->toBeInstanceOf(Solution::class);
    expect($solution->getSolutionTitle())->not->toBe('');
    expect($solution->getDocumentationLinks())->toHaveKey('Package documentation');
});
