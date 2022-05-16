<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid\Json;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Query\Json;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class JsonWhereTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function where_string()
    {
        $query = DB::table('test')->where('foo', 'bar')->whereJsonString('data->value1', 'foo');

        $this->assertEquals(
            "select * from `test` where `foo` = ? and JSON_EXTRACT_STRING(data, 'value1') = ?",
            $query->toSql()
        );
    }

    /** @test */
    public function or_where_string()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonString('data->value1', 'foo');

        $this->assertEquals(
            "select * from `test` where `foo` = ? or JSON_EXTRACT_STRING(data, 'value1') = ?",
            $query->toSql()
        );
    }

    /** @test */
    public function where_double()
    {
        $query = DB::table('test')->where('foo', 'bar')->whereJsonDouble('data->value1', 'foo');

        $this->assertEquals(
            "select * from `test` where `foo` = ? and JSON_EXTRACT_DOUBLE(data, 'value1') = ?",
            $query->toSql()
        );
    }

    /** @test */
    public function or_where_double()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonDouble('data->value1', 'foo');

        $this->assertEquals(
            "select * from `test` where `foo` = ? or JSON_EXTRACT_DOUBLE(data, 'value1') = ?",
            $query->toSql()
        );
    }

    /** @test */
    public function where_bigint()
    {
        $query = DB::table('test')->where('foo', 'bar')->whereJsonBigint('data->value1', 'foo');

        $this->assertEquals(
            "select * from `test` where `foo` = ? and JSON_EXTRACT_BIGINT(data, 'value1') = ?",
            $query->toSql()
        );
    }

    /** @test */
    public function or_where_bigint()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonBigint('data->value1', 'foo');

        $this->assertEquals(
            "select * from `test` where `foo` = ? or JSON_EXTRACT_BIGINT(data, 'value1') = ?",
            $query->toSql()
        );
    }

    /** @test */
    public function where_json()
    {
        $query = DB::table('test')->where('foo', 'bar')->whereJsonJson('data->value1', 'foo');

        $this->assertEquals(
            "select * from `test` where `foo` = ? and JSON_EXTRACT_JSON(data, 'value1') = ?",
            $query->toSql()
        );
    }

    /** @test */
    public function or_where_json()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonJson('data->value1', 'foo');

        $this->assertEquals(
            "select * from `test` where `foo` = ? or JSON_EXTRACT_JSON(data, 'value1') = ?",
            $query->toSql()
        );
    }

    /** @test */
    public function nested_where()
    {
        $query = DB::table('test')->where(function ($query) {
            $query->whereJsonString('data->value1', 'foo')->orWhere(function ($query) {
                $query->whereJsonBigint('data->value2', 1);
            });
        });

        $this->assertEquals(
            "select * from `test` where (JSON_EXTRACT_STRING(data, 'value1') = ? or (JSON_EXTRACT_BIGINT(data, 'value2') = ?))",
            $query->toSql()
        );
    }

    /** @test */
    public function where_null()
    {
        $query = DB::table('test')->whereNull('data->value1');

        $this->assertEquals(
        // @TODO check docs
            "select * from `test` where (JSON_EXTRACT_JSON(data, 'value1') is null OR json_type(JSON_EXTRACT_JSON(data, 'value1')) = 'NULL')",
            $query->toSql()
        );
    }

    /** @test */
    public function where_not_null()
    {
        $query = DB::table('test')->whereNotNull('data->value1');

        $this->assertEquals(
        // @TODO check docs
            "select * from `test` where (JSON_EXTRACT_JSON(data, 'value1') is not null AND json_type(JSON_EXTRACT_JSON(data, 'value1')) != 'NULL')",
            $query->toSql()
        );
    }

    /** @test */
    public function where_between()
    {
        $query = DB::table('test')->whereBetween('data->value1', [1, 10]);

        $this->assertEquals(
        // @TODO check docs
            "select * from `test` where (JSON_EXTRACT_JSON(data, 'value1') is not null AND json_type(JSON_EXTRACT_JSON(data, 'value1')) != 'NULL')",
            $query->toSql()
        );
    }
}
