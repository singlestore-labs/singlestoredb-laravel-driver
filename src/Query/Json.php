<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Query;

use Illuminate\Support\Str;

abstract class Json
{
    public const DOUBLE = 'DOUBLE';
    public const STRING = 'STRING';
    public const JSON = 'JSON';
    public const BIGINT = 'BIGINT';

    public static function wrap($type, $column)
    {
        return Str::start($column, strtoupper("SS_{$type}_SS->"));
    }

    public static function unwrap($column)
    {
        $column = preg_replace_callback("/^SS_(\w+)_SS\\->/", function ($matches) use (&$type) {
            // The type is captured as a group in the regex.
            $type = $matches[1];

            return '';
        }, $column);

        return [$type, $column];
    }

    public static function DOUBLE($column)
    {
        return static::wrap(static::DOUBLE, $column);
    }

    public static function STRING($column)
    {
        return static::wrap(static::STRING, $column);
    }

    public static function JSON($column)
    {
        return static::wrap(static::JSON, $column);
    }

    public static function BIGINT($column)
    {
        return static::wrap(static::BIGINT, $column);
    }
}