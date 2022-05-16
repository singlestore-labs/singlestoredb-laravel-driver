<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Query;

use SingleStore\Laravel\Exceptions\SingleStoreDriverException;
use SingleStore\Laravel\Exceptions\UnsupportedFunctionException;

trait JsonContainsMethods
{
    public function whereJsonContains($column, $value, $boolean = 'and', $not = false)
    {
        throw new UnsupportedFunctionException(
            'SingleStore does not support `whereJsonContains` without an explicit type. Please use one of ' .
            'the following: whereJsonContainsString whereJsonContainsDouble, whereJsonContainsJson.'
        );
    }

    public function orWhereJsonContains($column, $value)
    {
        throw new UnsupportedFunctionException(
            'SingleStore does not support `orWhereJsonContains` without an explicit type. Please use one of ' .
            'the following: orWhereJsonContainsString orWhereJsonContainsDouble, orWhereJsonContainsJson.'
        );
    }

    public function whereJsonDoesntContain($column, $value, $boolean = 'and')
    {
        throw new UnsupportedFunctionException(
            'SingleStore does not support `whereJsonDoesntContain` without an explicit type. Please use one of ' .
            'the following: whereJsonDoesntContainString whereJsonDoesntContainDouble, whereJsonDoesntContainJson.'
        );
    }

    public function orWhereJsonDoesntContain($column, $value)
    {
        throw new UnsupportedFunctionException(
            'SingleStore does not support `orWhereJsonDoesntContain` without an explicit type. Please use one of ' .
            'the following: orWhereJsonDoesntContainString orWhereJsonDoesntContainDouble, orWhereJsonDoesntContainJson.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | JSON Contains String
    |--------------------------------------------------------------------------
    */
    public function whereJsonContainsString($column, $value, $boolean = 'and', $not = false)
    {
        return $this->whereJsonContainType(Json::STRING, $column, $value, $boolean, $not);
    }

    public function orWhereJsonContainsString($column, $value)
    {
        return $this->orWhereJsonContainsType(Json::STRING, $column, $value);
    }

    public function whereJsonDoesntContainString($column, $value, $boolean = 'and')
    {
        return $this->whereJsonDoesntContainType(Json::STRING, $column, $value, $boolean);
    }

    public function orWhereJsonDoesntContainString($column, $value)
    {
        return $this->orWhereJsonDoesntContainType(Json::STRING, $column, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | JSON Contains Double
    |--------------------------------------------------------------------------
    */
    public function whereJsonContainsDouble($column, $value, $boolean = 'and', $not = false)
    {
        return $this->whereJsonContainType(Json::DOUBLE, $column, $value, $boolean, $not);
    }

    public function orWhereJsonContainsDouble($column, $value)
    {
        return $this->orWhereJsonContainsType(Json::DOUBLE, $column, $value);
    }

    public function whereJsonDoesntContainDouble($column, $value, $boolean = 'and')
    {
        return $this->whereJsonDoesntContainType(Json::DOUBLE, $column, $value, $boolean);
    }

    public function orWhereJsonDoesntContainDouble($column, $value)
    {
        return $this->orWhereJsonDoesntContainType(Json::DOUBLE, $column, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | JSON contains JSON
    |--------------------------------------------------------------------------
    */
    public function whereJsonContainsJson($column, $value, $boolean = 'and', $not = false)
    {
        return $this->whereJsonContainType(Json::JSON, $column, $value, $boolean, $not);
    }

    public function orWhereJsonContainsJson($column, $value)
    {
        return $this->orWhereJsonContainsType(Json::JSON, $column, $value);
    }

    public function whereJsonDoesntContainJson($column, $value, $boolean = 'and')
    {
        return $this->whereJsonDoesntContainType(Json::JSON, $column, $value, $boolean);
    }

    public function orWhereJsonDoesntContainJson($column, $value)
    {
        return $this->orWhereJsonDoesntContainType(Json::JSON, $column, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | Generic SingleStore typed json_contains methods.
    |--------------------------------------------------------------------------
    */
    public function whereJsonContainType($type, $column, $value, $boolean = 'and', $not = false)
    {
        parent::whereJsonContains($column, $value, $boolean, $not);

        return $this->retypeLastWhere($type);
    }

    public function orWhereJsonContainsType($type, $column, $value)
    {
        parent::whereJsonContains($column, $value, 'or');

        return $this->retypeLastWhere($type);
    }

    public function whereJsonDoesntContainType($type, $column, $value, $boolean = 'and')
    {
        parent::whereJsonContains($column, $value, $boolean, true);

        return $this->retypeLastWhere($type);
    }

    public function orWhereJsonDoesntContainType($type, $column, $value)
    {
        parent::whereJsonContains($column, $value, 'or', true);

        return $this->retypeLastWhere($type);
    }

    /*
    |--------------------------------------------------------------------------
    | Type helpers.
    |--------------------------------------------------------------------------
    */
    protected function retypeLastWhere($type)
    {
        $this->wheres[count($this->wheres) - 1]['type'] = $this->mapValueType($type);

        return $this;
    }

    protected function mapValueType($type)
    {
        // @TODO Carl: Bigint? It's not listed in the docs
        switch ($type) {
            case Json::JSON:
                return 'JsonContainsJson';
            case Json::STRING:
                return 'JsonContainsString';
            case Json::DOUBLE:
                return 'JsonContainsDouble';
        }

        throw new SingleStoreDriverException('Unknown json_contains type: ' . json_encode($type));
    }
}