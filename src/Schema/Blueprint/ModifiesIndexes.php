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
     * @param $name
     * @return \Illuminate\Support\Fluent
     */
    public function shardKey($columns, $name = null)
    {
        return $this->indexCommand('shardKey', $columns, $name);
    }

    /**
     * @param $columns
     * @param $name
     * @return \Illuminate\Support\Fluent
     */
    public function sortKey($columns, $name = null)
    {
        return $this->indexCommand('sortKey', $columns, $name);
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
