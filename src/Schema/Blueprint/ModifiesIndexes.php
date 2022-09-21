<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Schema\Blueprint;

use SingleStore\Laravel\Fluency\SpatialIndexCommand;

trait ModifiesIndexes
{
    /**
     * @param $columns
     * @return \Illuminate\Support\Fluent
     */
    public function shardKey($columns)
    {
        return $this->indexCommand('shardKey', $columns, 'shardKeyDummyName');
    }

    /**
     * @param $columns
     * @param $direction
     * @return \Illuminate\Support\Fluent
     */
    public function sortKey($columns, $direction = 'asc')
    {
        $command = $this->indexCommand('sortKey', $columns, 'sortKeyDummyName');
        $command->direction = $direction;

        return $command;
    }

    /**
     * @param $columns
     * @param $name
     * @return SpatialIndexCommand
     */
    public function spatialIndex($columns, $name = null)
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
