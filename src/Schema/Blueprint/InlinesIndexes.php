<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Schema\Blueprint;

use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Arr;

trait InlinesIndexes
{
    /**
     * The SingleStore indexes we're allowing the developer to use.
     *
     * @var string[]
     */
    protected $singleStoreIndexes = [
        'shardKey',
        'sortKey',
    ];

    /**
     * Taken from the parent class's `addFluentIndexes` method.
     *
     * @var string[]
     */
    protected $mysqlIndexes = [
        'primary',
        'unique',
        'index',
        'fulltext',
        'fullText',
        'spatialIndex',
    ];

    /**
     * The keys of the commands that are indexes
     *
     * @var int[]
     */
    protected $indexCommandKeys = [];

    /**
     * Given a set of statements from the `toSQL` method, inline all
     * of the indexes into the CREATE TABLE statement.
     *
     * @param  array  $statements
     * @return array
     */
    protected function inlineCreateIndexStatements($statements)
    {
        // In the `addImpliedCommands` method we gathered up the keys of all the commands
        // that are index commands. Now that we're ready to compile the SQL we'll pull
        // all those statements out to sneak them into the CREATE TABLE statement.
        $indexStatements = Arr::only($statements, $this->indexCommandKeys);

        // Since we're putting the index statements inside the CREATE TABLE statement,
        // we pull them out of the statement list so that they don't run as ALTERs.
        Arr::forget($statements, $this->indexCommandKeys);

        $search = static::INDEX_PLACEHOLDER;

        if (! $indexStatements) {
            // If there are no index statements at all, we need to replace the preceding comma as well.
            $search = ", $search";
        }

        // In the `getColumns` method of our custom grammar, we add a placeholder after the very
        // last column. We're going to replace it with the statements that create the indexes.
        $statements[0] = str_replace($search, implode(', ', $indexStatements), $statements[0]);

        return $statements;
    }

    /**
     * Get all of the index commands out of the blueprint's command queue.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function indexCommands()
    {
        return $this->commandsNamed(array_merge(
            $this->singleStoreIndexes,
            $this->mysqlIndexes
        ));
    }

    /**
     * @param  Grammar  $grammar
     * @return void
     */
    protected function addImpliedCommands(Grammar $grammar)
    {
        parent::addImpliedCommands($grammar);

        $this->addFluentSingleStoreIndexes();

        if ($this->creating()) {
            // We have to pull the keys for the indexes during this method because once
            // compiled, the primary key's `name` attribute is set to null, meaning
            // that we can no longer tell what type of key it is. By hooking into
            // the `addImpliedCommands` method, we access it before compilation.
            $this->indexCommandKeys = $this->indexCommands()->keys()->toArray();
        }
    }

    /**
     * @return void
     */
    protected function addFluentSingleStoreIndexes()
    {
        // This is modeled from the parent class, but with one major difference. In the
        // parent class, after an index is found `continue 2` is called, eliminating
        // the possibility that a single column has two fluent keys on it. For us,
        // a column can be a combination of primary key, shard key, or sort key.
        foreach ($this->columns as $column) {
            foreach ($this->singleStoreIndexes as $index) {
                if (isset($column->{$index})) {
                    $command = $this->{$index}($column->name, ($column->{$index} === true ? null : $column->{$index}));

                    // Forward with attributes if sortKey
                    if ($index === 'sortKey' && isset($column->with)) {
                        $command->with($column->with);
                        $column->with = null;
                    }

                    $column->{$index} = false;
                }
            }
        }
    }
}
