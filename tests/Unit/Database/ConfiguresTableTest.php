<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Tests\Fixtures\ConfiguredTableModel;
use Tests\Fixtures\UnconfiguredTableModel;

test('a model without the table properties falls back to the defaults', function (): void {
    $model = new UnconfiguredTableModel();

    config(['ancestry' => array_diff_key(config('ancestry'), ['table_name' => true])]);
    expect($model->getTable())->toBe('hierarchies');

    config(['ancestry.table_name' => 'custom_ancestors']);
    expect($model->getTable())->toBe('custom_ancestors');
});

test('a model with its own table properties uses them', function (): void {
    $model = new ConfiguredTableModel();

    expect($model->getTable())->toBe('custom_default');

    config(['ancestry.custom_key' => 'custom_configured']);
    expect($model->getTable())->toBe('custom_configured');
});
