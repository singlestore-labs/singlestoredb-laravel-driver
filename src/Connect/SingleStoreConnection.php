<?php

namespace SingleStore\Laravel\Connect;

use Illuminate\Database\MySqlConnection;
use SingleStore\Laravel\Query;
use SingleStore\Laravel\Schema;

class SingleStoreConnection extends MySqlConnection
{
    /**
     * Get a schema builder instance for the connection.
     *
     * @return Schema\SingleStoreBuilder
     */
    public function getSchemaBuilder(): Schema\SingleStoreBuilder
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new Schema\SingleStoreBuilder($this);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return Query\SingleStoreGrammar
     */
    protected function getDefaultQueryGrammar(): Query\SingleStoreGrammar
    {
        $grammar = new Query\SingleStoreGrammar($this->getConfig('ignore_order_by_in_deletes'), $this->getConfig('ignore_order_by_in_updates'));
        if (method_exists($grammar, 'setConnection')) {
            $grammar->setConnection($this);
        }

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return Schema\SingleStoreGrammar
     */
    protected function getDefaultSchemaGrammar(): Schema\SingleStoreGrammar
    {
        $grammar = new Schema\SingleStoreGrammar;
        if (method_exists($grammar, 'setConnection')) {
            $grammar->setConnection($this);
        }

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get a new query builder instance.
     *
     * @return Query\Builder
     */
    public function query(): Query\Builder
    {
        return new Query\Builder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }
}
