<?php

namespace SingleStore\Laravel\Query;

use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Support\Facades\Log;

class SingleStoreGrammar extends MySqlGrammar
{
    private bool $ignoreOrderByInDeletes;

    private bool $ignoreOrderByInUpdates;

    public function __construct(bool $ignoreOrderByInDeletes, bool $ignoreOrderByInUpdates)
    {
        $this->ignoreOrderByInDeletes = $ignoreOrderByInDeletes;
        $this->ignoreOrderByInUpdates = $ignoreOrderByInUpdates;
    }

    public function compileOptions(array $options): string
    {
        $optionString = '';
        foreach ($options as $key => $value) {
            if (! empty($optionString)) {
                $optionString .= ',';
            }
            $optionString .= $key.'='.$value;
        }

        return "OPTION ({$optionString})";
    }

    public function compileDelete(Builder $query): string
    {
        // TODO: allow order by in the case when table has unique column
        if (isset($query->orders)) {
            if ($this->ignoreOrderByInDeletes) {
                if (env('APP_ENV') !== 'production') {
                    Log::warning('SingleStore does not support the "ORDER BY" clause in a "DELETE" statement. The "ORDER BY" clause will be ignored.');
                }
                $query->orders = [];
            } else {
                throw new Exception('SingleStore does not support the "ORDER BY" clause in a "DELETE" statement. Enable the "ignore_order_by_in_deletes" configuration to ignore "orderBy" in "delete" operations.');
            }
        }

        return parent::compileDelete($query);
    }

    public function compileUpdate(Builder $query, array $values): string
    {
        // TODO: allow order by in the case when table has unique column
        if (isset($query->orders)) {
            if ($this->ignoreOrderByInUpdates) {
                if (env('APP_ENV') !== 'production') {
                    Log::warning('SingleStore does not support the "ORDER BY" clause in an "UPDATE" statement. The "ORDER BY" clause will be ignored.');
                }
                $query->orders = [];
            } else {
                throw new Exception('SingleStore does not support the "ORDER BY" clause in an update statement. Enable the "ignore_order_by_in_updates" configuration to ignore "orderBy" in "update" operations.');
            }
        }

        return parent::compileUpdate($query, $values);
    }

    /**
     * Compile a "where fulltext" clause.
     *
     * @param Builder $query
     * @param array $where
     * @return string
     */
    public function whereFullText(Builder $query, $where): string
    {
        $columns = $this->columnize($where['columns']);

        $value = $this->parameter($where['value']);

        return "MATCH ({$columns}) AGAINST ({$value})";
    }

    /**
     * @return string
     */
    protected function compileJsonContains($column, $value): string
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        // JSON_ARRAY_CONTAINS_[TYPE] doesn't support paths, so
        // we have to pass it through JSON_EXTRACT_JSON first.
        if ($path) {
            $field = "JSON_EXTRACT_JSON($field$path)";
        }

        return "JSON_ARRAY_CONTAINS_JSON($field, $value)";
    }

    protected function compileJsonUpdateColumn($key, $value): string
    {
        if (is_bool($value)) {
            $value = $value ? "'true'" : "'false'";
        } else {
            $value = $this->parameter($value);
        }

        // Break apart the column name from the JSON keypath.
        [$field, $path] = $this->wrapJsonFieldAndPath($key);

        return "$field = JSON_SET_JSON($field$path, $value)";
    }

    public function prepareBindingsForUpdate(array $bindings, array $values): array
    {
        // We need to encode strings for JSON columns, but we'll
        // let the parent class handle everything else.
        $values = collect($values)->map(function ($value, $column) {
            return $this->isJsonSelector($column) && is_string($value) ? json_encode($value) : $value;
        })->all();

        return parent::prepareBindingsForUpdate($bindings, $values);
    }

    /**
     * Transforms expressions to their scalar types.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string|int|float  $expression
     * @return string|int|float
     */
    public function getValue($expression): string|int|float
    {
        if ($this->isExpression($expression)) {
            return $this->getValue($expression->getValue($this));
        }

        return $expression;
    }

    protected function whereNull(Builder $query, $where): string
    {
        $columnValue = (string) $this->getValue($where['column']);
        if ($this->isJsonSelector($columnValue)) {
            [$field, $path] = $this->wrapJsonFieldAndPath($where['column']);

            return '(JSON_EXTRACT_JSON('.$field.$path.') IS NULL OR JSON_GET_TYPE(JSON_EXTRACT_JSON('.$field.$path.')) = \'NULL\')';
        }

        return $this->wrap($where['column']).' is null';
    }

    protected function whereNotNull(Builder $query, $where): string
    {
        $columnValue = (string) $this->getValue($where['column']);
        if ($this->isJsonSelector($columnValue)) {
            [$field, $path] = $this->wrapJsonFieldAndPath($where['column']);

            return '(JSON_EXTRACT_JSON('.$field.$path.') IS NOT NULL AND JSON_GET_TYPE(JSON_EXTRACT_JSON('.$field.$path.')) != \'NULL\')';
        }

        return $this->wrap($where['column']).' is not null';
    }

    protected function wrapJsonSelector($value)
    {
        // Break apart the column name from the JSON keypath.
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        if (! $path) {
            return $field;
        }

        return "JSON_EXTRACT_STRING($field$path)";
    }

    protected function wrapJsonBooleanSelector($value)
    {
        return str_replace(
            'JSON_EXTRACT_STRING',
            'JSON_EXTRACT_DOUBLE',
            $this->wrapJsonSelector($value)
        );
    }

    protected function wrapJsonFieldAndPath($column): array
    {
        // Matches numbers surrounded by brackets.
        $arrayAccessPattern = "/\\[(\d+)\\]/";

        // Turn all array access e.g. `data[0]` into `data->[0]`
        $column = preg_replace_callback($arrayAccessPattern, function ($matches) {
            return "->[$matches[1]]";
        }, $column);

        $parts = explode('->', $column);

        // The field must be unquoted, so shift it off first.
        $field = array_shift($parts);

        $parts = array_map(function ($part) use ($arrayAccessPattern) {
            // Array access indexes need to be real numbers, not strings.
            if (preg_match($arrayAccessPattern, $part, $matches)) {
                return (int) $matches[1];
            }

            // Named keys need to be strings.
            return "'$part'";
        }, $parts);

        $path = count($parts) ? ', '.implode(', ', $parts) : '';

        return [$field, $path];
    }

    /**
     * Wrap a union subquery in parentheses.
     *
     * @param  string  $sql
     * @return string
     */
    protected function wrapUnion($sql): string
    {
        return 'SELECT * FROM ('.$sql.')';
    }

    /**
     * Compile the "union" queries attached to the main query.
     *
     * @return string
     */
    protected function compileUnions(Builder $query): string
    {
        $sql = '';

        foreach ($query->unions as $union) {
            $sql .= $this->compileUnion($union);
        }

        return ltrim($sql);
    }

    /**
     * Compile a single union statement.
     *
     * @return string
     */
    protected function compileUnion(array $union): string
    {
        $conjunction = $union['all'] ? ' union all ' : ' union ';

        return $conjunction.'('.$union['query']->toSql().')';
    }

    /**
     * Compile a select query into SQL.
     *
     * @return string
     */
    public function compileSelect(Builder $query): string
    {
        $isAggregateWithUnionOrHaving = (($query->unions || $query->havings) && $query->aggregate);

        $sql = parent::compileSelect($query);

        if ($isAggregateWithUnionOrHaving) {
            return ltrim($sql);
        }

        if (! empty($query->unionOrders) || isset($query->unionLimit) || isset($query->unionOffset)) {
            $sql = 'SELECT * FROM ('.$sql.') ';

            if (! empty($query->unionOrders)) {
                $sql .= ' '.$this->compileOrders($query, $query->unionOrders);
            }

            if (isset($query->unionLimit)) {
                $sql .= ' '.$this->compileLimit($query, $query->unionLimit);
            }

            if (isset($query->unionOffset)) {
                $sql .= ' '.$this->compileUnionOffset($query, $query->unionOffset);
            }
        }

        return ltrim($sql);
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @return string
     */
    protected function compileOffset(Builder $query, $offset): string
    {
        return $this->compileOffsetWithLimit($offset, $query->limit);
    }

    /**
     * Compile the "offset" portions of the final union query.
     */
    protected function compileUnionOffset(Builder $query, $offset): string
    {
        return $this->compileOffsetWithLimit($offset, $query->unionLimit);
    }

    /**
     * Compile the "offset" portions of the query taking into account "limit" portion.
     */
    private function compileOffsetWithLimit($offset, $limit): string
    {
        // OFFSET is not valid without LIMIT
        // Add a huge LIMIT clause
        if (! isset($limit)) {
            // 9223372036854775807 - max 64-bit integer
            return ' LIMIT 9223372036854775807 OFFSET '.(int) $offset;
        }

        return ' OFFSET '.(int) $offset;
    }

    /**
     * Compile a delete statement with joins into SQL.
     *
     * @param  string  $table
     * @param  string  $where
     */
    protected function compileDeleteWithJoins(Builder $query, $table, $where): string
    {
        $joins = $this->compileJoins($query, $query->joins);

        // SingleStore does not support "database.table" in a delete statement when the delete statement contains a join
        // strip the database name from the table, if it exists
        $deleteTable = last(explode('.', $table));

        return "delete {$deleteTable} from {$table} {$joins} {$where}";
    }

    public function useLegacyGroupLimit(Builder $query): false
    {
        return false;
    }
}
