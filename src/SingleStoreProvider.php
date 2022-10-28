<?php

namespace SingleStore\Laravel;

use Illuminate\Support\ServiceProvider;
use SingleStore\Laravel\Connect\Connection;
use SingleStore\Laravel\Connect\Connector;

class SingleStoreProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        Connection::resolverFor('singlestore', function ($connection, $database, $prefix, $config) {
            return new Connection($connection, $database, $prefix, $config);
        });
    }

    public function boot()
    {
        $this->app->bind('db.connector.singlestore', Connector::class);
    }
}
