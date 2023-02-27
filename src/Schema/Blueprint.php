<?php

namespace SingleStore\Laravel\Schema;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
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

    /**
     * Execute the blueprint against the database.
     *
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar)
    {
        try {
            parent::build($connection, $grammar);
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'FULLTEXT KEY with unsupported type')) {
                throw new Exception('FULLTEXT is not supported when using the utf8mb4 collation.');
            } else {
                throw $exception;
            }
        }
    }
}
