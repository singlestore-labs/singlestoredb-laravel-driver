<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Unit;

use Illuminate\Foundation\Application;

if (version_compare(Application::VERSION, '9.0.0') >= 0) {
    trait MocksGetConnection
    {
        // Laravel 9
        protected function getConnection($connection = null, $table = null)
        {
            return $this->versionAgnosticOverride();
        }
    }
} else {
    trait MocksGetConnection
    {
        // Laravel 8
        protected function getConnection($connection = null)
        {
            return $this->versionAgnosticOverride();
        }
    }
}