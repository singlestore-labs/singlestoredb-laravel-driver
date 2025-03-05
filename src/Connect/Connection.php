<?php

namespace SingleStore\Laravel\Connect;

use Illuminate\Database\MySqlConnection;
use SingleStore\Laravel\Query\SingleStoreQueryBuilder;
use SingleStore\Laravel\Query\SingleStoreQueryGrammar;
use SingleStore\Laravel\Schema\SingleStoreSchemaGrammar;
use SingleStore\Laravel\Schema\SingleStoreSchemaBuilder;

class Connection extends MySqlConnection
{
    public function getSchemaBuilder()
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new SingleStoreSchemaBuilder($this);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return SingleStoreQueryGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new SingleStoreQueryGrammar(
            connection: $this,
            ignoreOrderByInDeletes: $this->getConfig('ignore_order_by_in_deletes'),
            ignoreOrderByInUpdates: $this->getConfig('ignore_order_by_in_updates')
        );
    }

    /**
     * Get the default schema grammar instance.
     */
    protected function getDefaultSchemaGrammar()
    {
        return new SingleStoreSchemaGrammar($this);
    }

    /**
     * Get a new query builder instance.
     */
    public function query()
    {
        return new SingleStoreQueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }
}
