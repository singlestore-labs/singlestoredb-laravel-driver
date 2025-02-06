<?php

namespace SingleStore\Laravel\Schema\Grammar;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

trait ModifiesColumns
{
    protected bool $singleStoreModifiersAdded = false;

    /**
     * Column modifiers specifically for SingleStore.
     *
     * @var string[]
     */
    protected array $singleStoreModifiers = [
        'Sparse', 'SeriesTimestamp', 'Option',
    ];

    protected function addSingleStoreModifiers(): void
    {
        if (! $this->singleStoreModifiersAdded) {
            // We need to insert all of our modifiers before the "after" modifier,
            // otherwise things like "sparse" will come after "after", which is
            // invalid SQL. So we find the position of the "after" modifier in
            // the parent, and then slot our modifiers in before it.
            $index = array_search('After', $this->modifiers);

            $this->modifiers = array_merge(
                array_slice($this->modifiers, 0, $index),
                $this->singleStoreModifiers,
                array_slice($this->modifiers, $index)
            );

            $this->singleStoreModifiersAdded = true;
        }
    }

    public function modifySparse(Blueprint $blueprint, Fluent $column): string
    {
        if (! is_null($column->sparse)) {
            return ' sparse';
        }

        return '';
    }

    public function modifySeriesTimestamp(Blueprint $blueprint, Fluent $column): string
    {
        if (! is_null($column->seriesTimestamp)) {
            return ' series timestamp';
        }

        return '';
    }

    public function modifyOption(Blueprint $blueprint, Fluent $column): string
    {
        if (! is_null($column->option)) {
            // @TODO docs?
            return " option '$column->option'";
        }

        return '';
    }

    /**
     * @deprecated
     */
    protected function modifyStoredAs(Blueprint $blueprint, Fluent $column): void
    {
        trigger_error('Method '.__METHOD__.' is deprecated', E_USER_DEPRECATED);

        // This is handled in the `getType` method of the Grammar, since
        // SingleStore requires it come before the column type.
    }

    protected function modifyVirtualAs(Blueprint $blueprint, Fluent $column): void
    {
        if (! is_null($column->virtualAs)) {
            throw new Exception('SingleStore does not support virtual computed columns. Use `storedAs` instead.');
        }
    }
}
