<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Schema\Grammar;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

trait CompilesKeys
{
    public function compileShardKey(Blueprint $blueprint, Fluent $command)
    {
        return "shard key({$this->columnize($command->columns)})";
    }

    public function compileSortKey(Blueprint $blueprint, Fluent $command)
    {
        if (is_array($command->with)) {
            $compiled = collect($command->with)->map(function ($value, $variable) {
                return "{$variable}={$value}";
            })->join(',');

            return "sort key({$this->columnizeWithDirection($command->columns, $command->direction)}) with ({$compiled})";
        }

        return "sort key({$this->columnizeWithDirection($command->columns, $command->direction)})";
    }

    public function compileSpatialIndex(Blueprint $blueprint, Fluent $command)
    {
        // SingleStore's spatial indexes just use the keyword `index`, not `spatial index`.
        $compiled = $this->compileKey($blueprint, $command, 'index');

        if ($command->resolution) {
            $compiled .= " with (resolution = $command->resolution)";
        }

        return $compiled;
    }
}
