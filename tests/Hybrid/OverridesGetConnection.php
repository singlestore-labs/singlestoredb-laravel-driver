<?php

namespace SingleStore\Laravel\Tests\Hybrid;

trait OverridesGetConnection
{
    protected function getConnection($connection = null, $table = null)
    {
        return $this->getDatabaseConnection($connection, $table);
    }
}
