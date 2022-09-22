<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use SingleStore\Laravel\Schema\Blueprint\AddsTableFlags;
use SingleStore\Laravel\Schema\Blueprint\InlinesIndexes;
use SingleStore\Laravel\Schema\Blueprint\ModifiesIndexes;

class Blueprint extends BaseBlueprint
{
    use AddsTableFlags, ModifiesIndexes, InlinesIndexes;

    public const INDEX_PLACEHOLDER = '__singlestore_indexes__';

    public function geography($column)
    {
        return $this->addColumn('geography', $column);
    }

    public function geographyPoint($column)
    {
        return $this->point($column);
    }

    public function toSql(Connection $connection, Grammar $grammar)
    {
        $statements = parent::toSql($connection, $grammar);

        return $this->creating() ? $this->inlineCreateIndexStatements($statements) : $statements;
    }

    /**
     * Specify a fulltext for the table.
     *
     * @param  string|array  $columns
     * @param  string|null  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Database\Schema\IndexDefinition
     */
    public function fullText($columns, $name = null, $algorithm = null)
    {
        /**
         * SingleStore only supports FULLTEXT indexes when using the utf8 collation.
         * https://docs.singlestore.com/managed-service/en/reference/sql-reference/data-definition-language-ddl/create-table.html#fulltext-behavior
         */
        $this->charset = 'utf8';
        $this->collation = 'utf8_unicode_ci';

        return $this->indexCommand('fulltext', $columns, $name, $algorithm);
    }
}
