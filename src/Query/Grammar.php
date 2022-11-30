<?php

namespace SingleStore\Laravel\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;

class Grammar extends MySqlGrammar
{
    /**
     * @param $column
     * @param $value
     * @return string
     */
    protected function compileJsonContains($column, $value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        // JSON_ARRAY_CONTAINS_[TYPE] doesn't support paths, so
        // we have to pass it through JSON_EXTRACT_JSON first.
        if ($path) {
            $field = "JSON_EXTRACT_JSON($field$path)";
        }

        return "JSON_ARRAY_CONTAINS_JSON($field, $value)";
    }

    protected function compileJsonUpdateColumn($key, $value)
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

    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        // We need to encode strings for JSON columns, but we'll
        // let the parent class handle everything else.
        $values = collect($values)->map(function ($value, $column) {
            return $this->isJsonSelector($column) && is_string($value) ? json_encode($value) : $value;
        })->all();

        return parent::prepareBindingsForUpdate($bindings, $values);
    }

    protected function whereNull(Builder $query, $where)
    {
        return $this->modifyNullJsonExtract(parent::whereNull($query, $where));
    }

    protected function whereNotNull(Builder $query, $where)
    {
        return $this->modifyNullJsonExtract(parent::whereNotNull($query, $where));
    }

    protected function modifyNullJsonExtract($statement)
    {
        return str_replace('json_extract(', 'JSON_EXTRACT_JSON(', $statement);
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

    protected function wrapJsonFieldAndPath($column)
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
     * @param Builder $query
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
     * Compile a select query into SQL.
     *
     * @param Builder $query
     * @return string
     */
    public function compileSelect(Builder $query): string
    {
        $sql = parent::compileSelect($query);

        if (! empty($query->unionOrders) || isset($query->unionLimit) || isset($query->unionOffset)) {
            $sql = "SELECT * FROM (".$sql.") ";

            if (! empty($query->unionOrders)) {
                $sql .= ' '.$this->compileOrders($query, $query->unionOrders);
            }

            if (isset($query->unionLimit)) {
                $sql .= ' '.$this->compileLimit($query, $query->unionLimit);
            }

            if (isset($query->unionOffset)) {
                $sql .= ' '.$this->compileOffset($query, $query->unionOffset);
            }
        }

        return ltrim($sql);
    }

}
