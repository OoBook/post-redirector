<?php

namespace Oobook\PostRedirector\Tests;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Oobook\Database\Eloquent\ManageEloquentServiceProvider;
use Oobook\PostRedirector\PostRedirectorServiceProvider;
use Oobook\Snapshot\SnapshotServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Note: this also flushes the cache from within the migration
        $this->setUpDatabase($this->app);

    }

    protected function getPackageProviders($app)
    {
      return [
        PostRedirectorServiceProvider::class,
      ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testdb');
        $app['config']->set('database.connections.testdb', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.prefix', 'spatie_tests---');
        $app['config']->set('cache.default', getenv('CACHE_DRIVER') ?: 'array');

    }

    /**
     * Set up the database.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function setUpDatabase($app)
    {

    }
}
