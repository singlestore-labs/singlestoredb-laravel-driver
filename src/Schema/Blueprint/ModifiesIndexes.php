<?php

namespace SingleStore\Laravel\Schema\Blueprint;

use SingleStore\Laravel\Fluency\SpatialIndexCommand;

trait ModifiesIndexes
{
    /**
     * @return \Illuminate\Support\Fluent
     */
    public function shardKey($columns)
    {
        return $this->indexCommand('shardKey', $columns, 'shardKeyDummyName');
    }

    /**
     * @return \Illuminate\Support\Fluent
     */
    public function sortKey($columns = null, $direction = 'asc')
    {
        $command = $this->indexCommand('sortKey', $columns, 'sortKeyDummyName');
        $command->direction = $direction;

        return $command;
    }

    /**
     * @return SpatialIndexCommand
     */
    public function spatialIndex(...$args)
    {
        $columns = $args[0] ?? null;
        $name = $args[1] ?? null;
        $operatorClass = $args[2] ?? null;

        // Laravel 12.21.0+ passes: $columns, $name = null, $operatorClass = null
        // Laravel <12.21.0 passes: $columns, $name = null
        if ((new \ReflectionMethod(parent::class, 'spatialIndex'))->getNumberOfParameters() === 3) {
            return parent::spatialIndex($columns, $name, $operatorClass);
        } else {
            return parent::spatialIndex($columns, $name);
        }

        return $this->recastLastCommand(SpatialIndexCommand::class);
    }

    /**
     * Recast the last fluent command into a different class,
     * which is helpful for IDE completion.
     *
     * @template T
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function recastLastCommand($class)
    {
        return $this->commands[] = new $class(array_pop($this->commands)->getAttributes());
    }
}
