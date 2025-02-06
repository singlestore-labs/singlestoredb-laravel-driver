<?php

namespace SingleStore\Laravel\Schema\Blueprint;

use Illuminate\Support\Fluent;
use SingleStore\Laravel\Fluency\SpatialIndexCommand;

trait ModifiesIndexes
{
    /**
     * @param $columns
     * @return Fluent
     */
    public function shardKey($columns): \Illuminate\Support\Fluent
    {
        return $this->indexCommand('shardKey', $columns, 'shardKeyDummyName');
    }

    /**
     * @param null $columns
     * @param string $direction
     * @return Fluent
     */
    public function sortKey($columns = null, $direction = 'asc'): Fluent
    {
        $command = $this->indexCommand('sortKey', $columns, 'sortKeyDummyName');
        $command->direction = $direction;

        return $command;
    }

    /**
     * @param $columns
     * @param null $name
     * @return SpatialIndexCommand
     */
    public function spatialIndex($columns, $name = null): SpatialIndexCommand
    {
        parent::spatialIndex($columns, $name);

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
