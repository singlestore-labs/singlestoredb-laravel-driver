<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Exception;
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
     * Execute the blueprint against the database.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar)
    {
        try {
            foreach ($this->toSql($connection, $grammar) as $statement) {
                $connection->statement($statement);
            }
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'FULLTEXT KEY with unsupported type')){
                throw new Exception('FULLTEXT is not supported when using the utf8mb4 collation.');
            } else {
                throw $exception;
            }
        }
    }
}
