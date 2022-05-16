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

    protected static $withJsonType;

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $before = count($this->wheres);

        $result = parent::where($column, $operator, $value, $boolean);

        // Test each `where` that was added to see if it was a column with a JSON accessor.
        // If so, we'll try to set the JSON type, but if we can't we throw an exception.
        // We could potentially infer the correct casting type based on the data, but
        // that could lead to runtime errors based on user input, which is not ideal.
        $this->mapAddedWheres($before, function ($where) {
            if (!$this->isJsonColumn($where)) {
                return $where;
            }

            return $this->addJsonTypeModifier($where);
        });

        return $result;
    }

    public function whereJson($type, $column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->withJsonType($type, function () use ($column, $operator, $value, $boolean) {
            return $this->where($column, $operator, $value, $boolean);
        });
    }

    public function orWhereJson($type, $column, $operator = null, $value = null)
    {
        $this->whereJson($type, $column, $operator, $value, 'or');
    }

    public function whereJsonDouble($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->whereJson(Json::DOUBLE, $column, $operator, $value, $boolean);
    }

    public function orWhereJsonDouble($column, $operator = null, $value = null)
    {
        return $this->whereJsonDouble($column, $operator, $value, 'or');
    }

    public function whereJsonString($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->whereJson(Json::STRING, $column, $operator, $value, $boolean);
    }

    public function orWhereJsonString($column, $operator = null, $value = null)
    {
        return $this->whereJsonString($column, $operator, $value, 'or');
    }

    public function whereJsonJson($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->whereJson(Json::JSON, $column, $operator, $value, $boolean);
    }

    public function orWhereJsonJson($column, $operator = null, $value = null)
    {
        return $this->whereJsonJson($column, $operator, $value, 'or');
    }

    public function whereJsonBigint($column, $operator = null, $value = null, $boolean = 'and')
    {
        return $this->whereJson(Json::BIGINT, $column, $operator, $value, $boolean);
    }

    public function orWhereJsonBigint($column, $operator = null, $value = null)
    {
        return $this->whereJsonBigint($column, $operator, $value, 'or');
    }

    public function withJsonType($type, $callable)
    {
        $cached = static::$withJsonType;

        static::$withJsonType = $type;

        $result = call_user_func($callable);

        static::$withJsonType = $cached;

        return $result;
    }

    protected function addJsonTypeModifier($where)
    {
        // It's possible they used the Json::{TYPE}() helper to wrap the column name. If
        // they have, then we defer to that. Otherwise we'll check to see if there's a
        // global JSON type set. If neither of those work then we throw an exception.
        [$type, $column] = Json::unwrap($where['column']);

        $type = $type ?? static::$withJsonType;

        if (is_null($type)) {
            throw new SingleStoreDriverException(
                'You must use one of the `whereJson*` methods to specify the JSON extraction type.'
            );
        }

        $where['column'] = Json::wrap($type, $column);

        return $where;
    }

    protected function isJsonColumn($where)
    {
        return array_key_exists('column', $where)
            && is_string($where['column'])
            && $where['type'] === 'Basic'
            && Str::contains($where['column'], '->');
    }

    protected function mapAddedWheres($from, $callback)
    {
        for ($i = $from; $i < count($this->wheres); $i++) {
            $this->wheres[$i] = call_user_func($callback, $this->wheres[$i]);
        }
    }
}