<?php

namespace SingleStore\Laravel\Schema;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SingleStore\Laravel\Schema\Blueprint as SingleStoreBlueprint;
use SingleStore\Laravel\Schema\Grammar\CompilesKeys;
use SingleStore\Laravel\Schema\Grammar\ModifiesColumns;

class Grammar extends MySqlGrammar
{
    use CompilesKeys;
    use ModifiesColumns;

    public function __construct()
    {
        // Before anything kicks off, we need to add the SingleStore modifiers
        // so that they'll get used while the columns are all compiling.
        $this->addSingleStoreModifiers();
    }

    /**
     * Create the column definition for a spatial Geography type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typeGeography(Fluent $column)
    {
        return 'geography';
    }

    /**
     * Create the column definition for a spatial Point type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    public function typePoint(Fluent $column)
    {
        // For SingleStore, `point` is invalid. It uses `geographypoint` instead.
        return 'geographypoint';
    }

    /**
     * Create the main create table clause.
     *
     * @param  Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array
     *
     * @throws Exception
     */
    protected function compileCreateTable($blueprint, $command, $connection)
    {
        // We want to do as little as possible ourselves, so we rely on the parent
        // to compile everything and then potentially sneak some modifiers in.
        return $this->insertCreateTableModifiers(
            $blueprint,
            parent::compileCreateTable($blueprint, $command, $connection)
        );
    }

    /**
     * @param  Fluent  $column
     * @return string
     */
    protected function getType(Fluent $column)
    {
        $type = parent::getType($column);

        if (! is_null($column->storedAs)) {
            // MySQL's syntax for stored columns is `<name> <datatype> as (<expression>) stored`,
            // but for SingleStore it's `<name> as (<expression>) persisted <datatype>`. Here
            // we sneak the expression in as a part of the type definition, so that it will
            // end up in the right spot. `modifyStoredAs` is a noop to account for this.
            $type = "as ($column->storedAs) persisted $type";
        }

        return $type;
    }

    /**
     * Append the engine specifications to a command.
     *
     * @param  string  $sql
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @return string
     */
    protected function compileCreateEngine($sql, Connection $connection, Blueprint $blueprint)
    {
        $sql = parent::compileCreateEngine($sql, $connection, $blueprint);

        // We're not actually messing with the engine part at all, this is just
        // a good place to add `compression = sparse` if it's called for.
        if ($blueprint->sparse) {
            $sql .= ' compression = sparse';
        }

        return $sql;
    }

    /**
     * @param $blueprint
     * @param $compiled
     * @return string
     *
     * @throws Exception
     */
    protected function insertCreateTableModifiers($blueprint, $compiled)
    {
        $replacement = 'create';

        if ($blueprint->rowstore) {
            $replacement .= ' rowstore';
        }

        if ($blueprint->reference) {
            $replacement .= ' reference';
        }

        if ($blueprint->global) {
            $replacement .= ' global';
        }

        return Str::replaceFirst('create ', "$replacement ", $compiled);
    }

    /**
     * @param  Blueprint  $blueprint
     * @return array
     *
     * @throws Exception
     */
    protected function getColumns(Blueprint $blueprint)
    {
        $columns = parent::getColumns($blueprint);

        if ($blueprint->creating()) {
            // Because all keys *must* be added at the time of table creation, we can't rely on
            // the normal ALTER TABLE commands that Laravel generates. Instead we add a fake
            // column so that it ends up in the right spot (last) inside the SQL statement.
            $columns[] = SingleStoreBlueprint::INDEX_PLACEHOLDER;
        }

        return $columns;
    }

    /**
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @param $type
     * @return array|string|string[]
     */
    protected function compileKey(Blueprint $blueprint, Fluent $command, $type)
    {
        $compiled = parent::compileKey($blueprint, $command, $type);

        // We don't mess with ALTER statements at all.
        if (! $blueprint->creating()) {
            return $compiled;
        }

        // All keys are added as a part of the CREATE TABLE statement. Completely
        // removing the `alter table %s add` gives us the right syntax for
        // creating the indexes as a part of the create statement.
        return str_replace(sprintf('alter table %s add ', $this->wrapTable($blueprint)), '', $compiled);
    }

    /**
     * Convert an array of column names into a delimited string (with direction parameter).
     *
     * @param  array  $columns
     * @return string
     */
    protected function columnizeWithDirection(array $columns, string $direction)
    {
        if ($columns === array_filter($columns, 'is_array')) {
            $columnNames = array_map(function ($column) {
                return $this->wrap($column[0]);
            }, $columns);

            $columnDirections = array_map(function ($column) {
                return $column[1];
            }, $columns);

            return implode(', ', array_map(function ($column, $direction) {
                return "$column $direction";
            }, $columnNames, $columnDirections));
        }

        if (array_filter($columns, 'is_array') !== []) {
            throw new InvalidArgumentException('You must set the direction for each sort key column or use the second parameter to set the direction for all sort key columns');
        }

        $wrapped = array_map([$this, 'wrap'], $columns);

        return implode(', ', array_map(function ($column) use ($direction) {
            return $column.' '.$direction;
        }, $wrapped));
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, Fluent $column)
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ($column->withoutPrimaryKey === true)
                ? ' auto_increment'
                : ' auto_increment primary key';
        }
    }

    /**
     * Compile a rename table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileRename(Blueprint $blueprint, Fluent $command)
    {
        $from = $this->wrapTable($blueprint);

        return "alter table {$from} rename to ".$this->wrapTable($command->to);
    }
}
