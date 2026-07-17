<?php

namespace Tests;

use Modules\Crud\CrudServiceProvider;
use MongoDB\Laravel\MongoDBServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class MongoDbTestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CrudServiceProvider::class,
            MongoDBServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('database.connections.mongodb', [
            'driver' => 'mongodb',
            'dsn' => getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1:27017',
            'database' => getenv('MONGODB_DATABASE') ?: 'crud_integration_test',
        ]);
    }
}
