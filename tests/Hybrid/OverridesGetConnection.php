<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Foundation\Application;

if (version_compare(Application::VERSION, '9.0.0', '>=')) {
    trait OverridesGetConnection
    {
        // Laravel 9
        protected function getConnection($connection = null, $table = null)
        {
            return $this->getDatabaseConnection($connection, $table);
        }
    }
} else {
    trait OverridesGetConnection
    {
        // Laravel 8
        protected function getConnection($connection = null)
        {
            return $this->getDatabaseConnection($connection);
        }
    }
}