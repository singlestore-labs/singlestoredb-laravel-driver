<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use SingleStore\Laravel\Exceptions\SingleStoreDriverException;

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

        // JSON_ARRAY_CONTAINS_[TYPE] doesn't support paths.
        if ($path) {
            $field = "JSON_EXTRACT_JSON($field$path)";
        }

        // Add a placeholder that we'll swap out based on the type.
        return "__SINGLE_STORE_JSON_TYPE__($field, $value)";
    }

    protected function compileJsonUpdateColumn($key, $value)
    {
        if (is_bool($value)) {
            $value = $value ? "'true'" : "'false'";
        } elseif (is_array($value)) {
            $value = 'cast(? as json)';
        } else {
            $value = $this->parameter($value);
        }

        // First we need to break out the SingleStore extraction
        // type from the actual column definition.
        [$type, $column] = Json::unwrap($key);

        // Then we break apart the column name from the JSON keypath.
        [$field, $path] = $this->wrapJsonFieldAndPath($column);

        if (!$type) {
            throw new SingleStoreDriverException(
                'You must provide a JSON type when performing an update. Please use one of the Json::[TYPE] methods.'
            );
        }

        return "$field = JSON_SET_$type($field$path, $value)";
    }

    /**
     * @param  Builder  $query
     * @param $where
     * @return string
     */
    protected function whereJsonContainsString(Builder $query, $where)
    {
        return $this->whereSingleStoreJsonContains($query, $where);
    }

    /**
     * @param  Builder  $query
     * @param $where
     * @return string
     */
    protected function whereJsonContainsDouble(Builder $query, $where)
    {
        return $this->whereSingleStoreJsonContains($query, $where);
    }

    /**
     * @param  Builder  $query
     * @param $where
     * @return string
     */
    protected function whereJsonContainsJson(Builder $query, $where)
    {
        return $this->whereSingleStoreJsonContains($query, $where);
    }

    protected function whereSingleStoreJsonContains($query, $where)
    {
        // Leverage the BaseGrammar to compile everything correctly for us.
        // The BaseGrammar defers to this class's `compileJsonContains`
        // that inserts our placeholder, which is what we replace.
        $placeheld = parent::whereJsonContains($query, $where);

        // The `where` contains a type set by the SingleStore Query Builder.
        // We use that type to determine the correct SingleStore method.
        $singlestore = $this->jsonArrayContainsType($where);

        return str_replace('__SINGLE_STORE_JSON_TYPE__', $singlestore, $placeheld);
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


    public function prepareBindingForJsonContains($binding)
    {
        // Don't json_encode these types.
        if (is_string($binding) || is_numeric($binding) || is_bool($binding)) {
            return $binding;
        }

        return parent::prepareBindingForJsonContains($binding);
    }

    protected function wrapJsonSelector($value)
    {
        // Break apart the column name from the JSON keypath.
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        if (!$path) {
            return $field;
        }

        return "JSON_EXTRACT_STRING($field$path)";
    }

    protected function wrapJsonBooleanSelector($value)
    {
        return $this->wrapJsonSelector(Json::DOUBLE($value));
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

        $path = count($parts) ? ', ' . implode(", ", $parts) : '';

        return [$field, $path];
    }

    protected function jsonArrayContainsType($where)
    {
        switch ($where['type']) {
            case 'JsonContainsString':
                return 'JSON_ARRAY_CONTAINS_STRING';

            case 'JsonContainsDouble':
                return 'JSON_ARRAY_CONTAINS_DOUBLE';

            case 'JsonContainsJson':
                return 'JSON_ARRAY_CONTAINS_JSON';
        }

        throw new SingleStoreDriverException('Unknown JSON_CONTAINS type.');
    }

}