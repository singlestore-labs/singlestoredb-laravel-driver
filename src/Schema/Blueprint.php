<?php

namespace SingleStore\Laravel\Schema;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
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
     * @param  string|null  $subtype
     * @param  int  $srid
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function geography($column, $subtype = null, $srid = 4326)
    {
        return $this->addColumn('geography', $column);
    }

    public function geographyPoint($column)
    {
        return $this->point($column);
    }

    /**
     * Create a new point column on the table.
     *
     * @param  string  $column
     * @param  int|null  $srid
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function point($column, $srid = null)
    {
        return $this->addColumn('point', $column);
    }

    /**
     * Execute the blueprint against the database.
     *
     * @return void
     */
    public function build()
    {
        try {
            parent::build();
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'FULLTEXT KEY with unsupported type')) {
                throw new Exception('FULLTEXT is not supported when using the utf8mb4 collation.');
            }

            throw $exception;
        }
    }
}
