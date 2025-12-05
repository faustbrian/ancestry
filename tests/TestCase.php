<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use Cline\Ancestry\AncestryServiceProvider;
use Cline\Ancestry\Database\ModelRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;
use Tests\Fixtures\Order;
use Tests\Fixtures\User;

use function env;
use function Orchestra\Testbench\artisan;
use function Orchestra\Testbench\package_path;

/**
 * Base test case for Ancestry package tests.
 *
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup the world for the tests.
     */
    #[Override()]
    protected function setUp(): void
    {
        parent::setUp();

        Mockery::close();

        // Clear booted models to prevent stale event listeners
        Model::clearBootedModels();

        // Configure morph key map for test models
        $this->app->make(ModelRegistry::class)->morphKeyMap([
            User::class => 'id',
            Order::class => 'id',
        ]);
    }

    /**
     * Clean up after each test.
     */
    #[Override()]
    protected function tearDown(): void
    {
        // Clear booted models after test to prevent contamination
        Model::clearBootedModels();
        $this->app->make(ModelRegistry::class)->reset();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Get package providers.
     *
     * @param  mixed                    $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AncestryServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param mixed $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('ancestry.primary_key_type', env('ANCESTRY_PRIMARY_KEY_TYPE', 'id'));
        $app['config']->set('ancestry.ancestor_morph_type', env('ANCESTRY_ANCESTOR_MORPH_TYPE', 'string'));
        $app['config']->set('ancestry.descendant_morph_type', env('ANCESTRY_DESCENDANT_MORPH_TYPE', 'string'));
        $app['config']->set('ancestry.max_depth', 10);
        $app['config']->set('ancestry.events.enabled', true);

        // Snapshot configuration
        $app['config']->set('ancestry.snapshots.enabled', true);
        $app['config']->set('ancestry.snapshots.table_name', 'hierarchy_snapshots');
        $app['config']->set('ancestry.snapshots.context_morph_type', env('ANCESTRY_SNAPSHOTS_CONTEXT_MORPH_TYPE', 'string'));
        $app['config']->set('ancestry.snapshots.ancestor_key_type', env('ANCESTRY_SNAPSHOTS_ANCESTOR_KEY_TYPE', 'id'));
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        artisan($this, 'migrate:install');

        $this->loadMigrationsFrom(package_path('database/migrations'));
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }
}
