<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Schema\Grammar;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

trait ModifiesColumns
{
    protected $singleStoreModifiersAdded = false;

    /**
     * Column modifiers specifically for SingleStore.
     *
     * @var string[]
     */
    protected $singleStoreModifiers = [
        'Sparse', 'Option', 'SeriesTimestamp'
    ];

    protected function addSingleStoreModifiers()
    {
        if (!$this->singleStoreModifiersAdded) {
            $this->modifiers = array_merge($this->modifiers, $this->singleStoreModifiers);
            $this->singleStoreModifiersAdded = true;
        }
    }

    public function modifySparse(Blueprint $blueprint, Fluent $column)
    {
        if (!is_null($column->sparse)) {
            return " sparse";
        }
    }

    public function modifySeriesTimestamp(Blueprint $blueprint, Fluent $column)
    {
        if (!is_null($column->seriesTimestamp)) {
            return " series timestamp";
        }
    }

    public function modifyOption(Blueprint $blueprint, Fluent $column)
    {
        if (!is_null($column->option)) {
            // @TODO docs?
            return " option '$column->option'";
        }
    }

    protected function modifyStoredAs(Blueprint $blueprint, Fluent $column)
    {
        // This is handled in the `getType` method of the Grammar, since
        // SingleStore requires it come before the column type.
    }

    protected function modifyVirtualAs(Blueprint $blueprint, Fluent $column)
    {
        if (!is_null($column->virtualAs)) {
            throw new Exception('SingleStore does not support virtual computed columns. Use `storedAs` instead.');
        }
    }
}