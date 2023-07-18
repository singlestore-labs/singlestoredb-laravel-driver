<?php

namespace SingleStore\Laravel\Connect;

use Illuminate\Database\MySqlConnection;
use SingleStore\Laravel\Query;
use SingleStore\Laravel\QueryGrammar;
use SingleStore\Laravel\Schema;
use SingleStore\Laravel\SchemaBuilder;
use SingleStore\Laravel\SchemaGrammar;

class Connection extends MySqlConnection
{
    /**
     * Get a schema builder instance for the connection.
     *
     * @return SchemaBuilder
     */
    public function getSchemaBuilder()
    {
        if (null === $this->schemaGrammar) {
            $this->useDefaultSchemaGrammar();
        }

        return new Schema\Builder($this);
    }

    /**
     * Get the default query grammar instance.
     *
     * @return QueryGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        $grammar = new Query\Grammar;
        if (method_exists($grammar, 'setConnection')) {
            $grammar->setConnection($this);
        }

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return SchemaGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        $grammar = new Schema\Grammar;
        if (method_exists($grammar, 'setConnection')) {
            $grammar->setConnection($this);
        }

        return $this->withTablePrefix($grammar);
    }

    /**
     * Get a new query builder instance.
     */
    public function query()
    {
        return new Query\Builder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }
}
