<?php

namespace SingleStore\Laravel\Tests;

use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;
use PDO;
use SingleStore\Laravel\SingleStoreProvider;

abstract class BaseTest extends TestCase
{
    public $loadEnvironmentVariables = true;

    protected function resolveApplication()
    {
        // Locally we'll load a .env file for our environment variables.
        // We could put them in phpunit.xml, except for the fact that
        // there are live credentials to a SingleStore cluster. When
        // running on GitHub, we use private repository secrets.
        return parent::resolveApplication()->useEnvironmentPath(dirname(__DIR__));
    }

    protected function getPackageProviders($app)
    {
        return [
            SingleStoreProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Use the default MySQL configuration from Laravel, but switch the driver
        // to `singlestore`. This ensures that we're doing the least amount of
        // configuration possible, making for the ideal developer experience.
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.driver', 'singlestore');
        $app['config']->set('database.connections.mysql.options.' . PDO::ATTR_EMULATE_PREPARES, true);
        $app['config']->set('database.connections.mysql.ignore_order_by_in_deletes', true);
        $app['config']->set('database.connections.mysql.ignore_order_by_in_updates', true);
    }

    public function singlestoreVersion()
    {
        return DB::select('SELECT @@memsql_version AS version')[0]->version;
    }
}
