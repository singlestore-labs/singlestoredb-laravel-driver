<?php

namespace SingleStore\Laravel\Schema\Blueprint;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use SingleStore\Laravel\Schema\Blueprint as SingleStoreBlueprint;

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
     * Given a set of statements from the `toSQL` method, inline all
     * of the indexes into the CREATE TABLE statement.
     *
     * @param  array  $statements
     * @return array
     */
    protected function inlineCreateIndexStatements($statements, $indexStatementKeys)
    {
        // In the `addImpliedCommands` method we gathered up the keys of all the commands
        // that are index commands. Now that we're ready to compile the SQL we'll pull
        // all those statements out to sneak them into the CREATE TABLE statement.
        $indexStatements = Arr::only($statements, $indexStatementKeys);

        // Since we're putting the index statements inside the CREATE TABLE statement,
        // we pull them out of the statement list so that they don't run as ALTERs.
        Arr::forget($statements, $indexStatementKeys);

        $search = SingleStoreBlueprint::INDEX_PLACEHOLDER;

        if (!$indexStatements) {
            // If there are no index statements at all, we need to replace the preceding comma as well.
            $search = ", $search";
        }

        // In the `getColumns` method of our custom grammar, we add a placeholder after the very
        // last column. We're going to replace it with the statements that create the indexes.
        $statements[0] = str_replace($search, implode(', ', $indexStatements), $statements[0]);

        return $statements;
    }

    /**
     * Check if the command is index.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function isIndexCommand($command)
    {
        return in_array($command->name, array_merge(
            $this->singleStoreIndexes,
            $this->mysqlIndexes
        ));
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
                    if ($column->{$index} === true) {
                        $command = $this->{$index}($column->name);
                    } else {
                        $command = $this->{$index}($column->name, $column->{$index});
                    }

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

    public function toSql(Connection $connection, Grammar $grammar)
    {
        if (version_compare(Application::VERSION, '10.0.0', '>=')) {
            $this->addImpliedCommands($connection, $grammar);
        } else {
            $this->addImpliedCommands($grammar);
        }
        $this->addFluentSingleStoreIndexes();

        $statements = [];
        $indexStatementKeys = [];

        foreach ($this->commands as $command) {
            $method = 'compile' . ucfirst($command->name);
            $isIndex = $this->isIndexCommand($command);

            if (method_exists($grammar, $method) || $grammar::hasMacro($method)) {
                if (!is_null($sql = $grammar->$method($this, $command, $connection))) {
                    $statements = array_merge($statements, (array) $sql);
                    if ($isIndex) {
                        array_push($indexStatementKeys, count($statements) - 1);
                    }
                }
            }
        }

        return $this->creating() ? $this->inlineCreateIndexStatements($statements, $indexStatementKeys) : $statements;
    }
}
