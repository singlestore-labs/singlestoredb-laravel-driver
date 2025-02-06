<?php

namespace SingleStore\Laravel\Tests\Hybrid;

trait OverridesGetConnection
{
    // Laravel 9
    protected function getConnection($connection = null, $table = null)
    {
        return $this->getDatabaseConnection($connection, $table);
    }
}
