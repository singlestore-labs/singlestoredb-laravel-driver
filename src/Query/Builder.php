<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Str;
use SingleStore\Laravel\Exceptions\SingleStoreDriverException;

class Builder extends BaseBuilder
{
    use JsonContainsMethods;

    protected $validateJsonTypes = true;

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $before = count($this->wheres);

        $result = parent::where($column, $operator, $value, $boolean);

        // Test each `where` that was added to see if it was a column with a JSON accessor.
        // If so, we need to warn the developer that that isn't supported. We could
        // potentially infer the correct casting type based on the data, but that
        // could lead to runtime errors based on user input, which is not ideal.
        $this->mapAddedWheres($before, function ($where) {
            if (!$this->validateJsonTypes || !$this->isJsonColumn($where)) {
                return $where;
            }

            return $this->validateJsonType($where);
        });

        return $result;
    }

    public function whereJson($type, $column, $operator = null, $value = null, $boolean = 'and')
    {
        $before = count($this->wheres);

        $return = $this->withoutJsonTypeValidation(function () use ($column, $operator, $value, $boolean) {
            return $this->where($column, $operator, $value, $boolean);
        });

        $this->mapAddedWheres($before, function ($where) use ($type) {
            if (!is_string($where['column'])) {
                throw new SingleStoreDriverException('Cannot extract JSON from a column that is not a string.');
            }

            $where['column'] = Json::wrap($type, $where['column']);

            return $where;
        });

        return $return;
    }

    public function whereJsonDouble($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->whereJson(Json::DOUBLE, $column, $operator, $value, $boolean);
    }

    public function whereJsonString($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->whereJson(Json::STRING, $column, $operator, $value, $boolean);
    }

    public function whereJsonJson($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->whereJson(Json::JSON, $column, $operator, $value, $boolean);
    }

    public function whereJsonBigint($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->whereJson(Json::BIGINT, $column, $operator, $value, $boolean);
    }

    public function withoutJsonTypeValidation($callable)
    {
        $cached = $this->validateJsonTypes;

        $this->validateJsonTypes = false;

        $result = call_user_func($callable);

        $this->validateJsonTypes = $cached;

        return $result;
    }

    protected function validateJsonType($where)
    {
        if ($where['type'] === 'Basic') {
            // It's possible they used the Json::TYPE() helper to
            // wrap their column name, so we'll check for that.
            // If they haven't we have to throw an error.
            [$type,] = Json::unwrap($where['column']);

            if (is_null($type)) {
                throw new SingleStoreDriverException(
                    'You must use the `whereJson` method to specify the JSON extraction type.'
                );
            }
        }

        return $where;
    }

    protected function isJsonColumn($where)
    {
        return array_key_exists('column', $where)
            && is_string($where['column'])
            && Str::contains($where['column'], '->');
    }

    protected function mapAddedWheres($from, $callback)
    {
        for ($i = $from; $i < count($this->wheres); $i++) {
            $this->wheres[$i] = call_user_func($callback, $this->wheres[$i]);
        }
    }
}