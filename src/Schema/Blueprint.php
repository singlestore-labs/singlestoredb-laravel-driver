<?php

namespace SingleStore\Laravel\Schema;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\Grammars\Grammar;
use SingleStore\Laravel\Schema\Blueprint\AddsTableFlags;
use SingleStore\Laravel\Schema\Blueprint\InlinesIndexes;
use SingleStore\Laravel\Schema\Blueprint\ModifiesIndexes;

class Blueprint extends BaseBlueprint
{
    use AddsTableFlags, InlinesIndexes, ModifiesIndexes;

    public const INDEX_PLACEHOLDER = '__singlestore_indexes__';

    /**
     * Create a new geography column on the table.
     *
     * @param  string  $column
     * @param  null  $subtype
     * @param  int  $srid
     */
    public function geography($column, $subtype = null, $srid = 4326): ColumnDefinition
    {
        return $this->addColumn('geography', $column);
    }

    public function geographyPoint($column): ColumnDefinition
    {
        return $this->point($column);
    }

    /**
     * Create a new point column on the table.
     */
    public function point(string $column): ColumnDefinition
    {
        return $this->addColumn('point', $column);
    }

    /**
     * Execute the blueprint against the database.
     *
     * @throws Exception
     */
    public function build(Connection $connection, Grammar $grammar): void
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
