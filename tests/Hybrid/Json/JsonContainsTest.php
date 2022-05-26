<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid\Json;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Exceptions\UnsupportedFunctionException;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class JsonContainsTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function json_contains_throws()
    {
        $this->expectException(UnsupportedFunctionException::class);
        $this->expectExceptionMessage('SingleStore does not support `whereJsonContains` without an explicit type. Please use one of');

        DB::table('test')->whereJsonContains('data->languages', 'en')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | JSON contains string
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function json_contains_string()
    {
        $query = DB::table('test')->whereJsonContainsString('data->array', 'en');

        $this->assertEquals(
            "select * from `test` where JSON_ARRAY_CONTAINS_STRING(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );

        $this->assertEquals('en', $query->getBindings()[0]);

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1,] = $this->insertJsonData([
            ['array' => ['en', 1, true, ['a' => 'b']]],
            ['array' => ['es', 2, false, ['c' => 'd']]]
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    /** @test */
    public function or_json_contains_string()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonContainsString('data->array', 'en');

        $this->assertEquals(
            "select * from `test` where `foo` = ? or JSON_ARRAY_CONTAINS_STRING(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );
    }

    /** @test */
    public function json_doesnt_contain_string()
    {
        $query = DB::table('test')->whereJsonDoesntContainString('data->array', 'en');

        $this->assertEquals(
            "select * from `test` where not JSON_ARRAY_CONTAINS_STRING(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );
    }

    /** @test */
    public function or_json_doesnt_contain_string()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonDoesntContainString('data->array', 'en');

        $this->assertEquals(
            "select * from `test` where `foo` = ? or not JSON_ARRAY_CONTAINS_STRING(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | JSON contains double
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function json_contains_double()
    {
        $query = DB::table('test')->whereJsonContainsDouble('data->array', 1);

        $this->assertEquals(
            "select * from `test` where JSON_ARRAY_CONTAINS_DOUBLE(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );

        $this->assertEquals(1, $query->getBindings()[0]);

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1,] = $this->insertJsonData([
            ['array' => ['en', 1, true, ['a' => 'b']]],
            ['array' => ['es', 2, false, ['c' => 'd']]]
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    /** @test */
    public function or_json_contains_double()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonContainsDouble('data->array', 1);

        $this->assertEquals(
            "select * from `test` where `foo` = ? or JSON_ARRAY_CONTAINS_DOUBLE(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );
    }

    /** @test */
    public function json_doesnt_contain_double()
    {
        $query = DB::table('test')->whereJsonDoesntContainDouble('data->array', 1);

        $this->assertEquals(
            "select * from `test` where not JSON_ARRAY_CONTAINS_DOUBLE(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );
    }

    /** @test */
    public function or_json_doesnt_contain_double()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonDoesntContainDouble('data->array', 1);

        $this->assertEquals(
            "select * from `test` where `foo` = ? or not JSON_ARRAY_CONTAINS_DOUBLE(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | JSON contains JSON
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function json_contains_json()
    {
        $query = DB::table('test')->whereJsonContainsJson('data->array', ['a' => 'b']);

        $this->assertEquals(
            "select * from `test` where JSON_ARRAY_CONTAINS_JSON(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );

        $this->assertEquals('{"a":"b"}', $query->getBindings()[0]);

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1,] = $this->insertJsonData([
            ['array' => ['en', 1, true, ['a' => 'b']]],
            ['array' => ['es', 2, false, ['c' => 'd']]]
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    /** @test */
    public function or_json_contains_json()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonContainsJson('data->array', ['a' => 'b']);

        $this->assertEquals(
            "select * from `test` where `foo` = ? or JSON_ARRAY_CONTAINS_JSON(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );
    }

    /** @test */
    public function json_doesnt_contain_json()
    {
        $query = DB::table('test')->whereJsonDoesntContainJson('data->array', ['a' => 'b']);

        $this->assertEquals(
            "select * from `test` where not JSON_ARRAY_CONTAINS_JSON(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );
    }

    /** @test */
    public function or_json_doesnt_contain_json()
    {
        $query = DB::table('test')->where('foo', 'bar')->orWhereJsonDoesntContainJson('data->array', ['a' => 'b']);

        $this->assertEquals(
            "select * from `test` where `foo` = ? or not JSON_ARRAY_CONTAINS_JSON(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );
    }
}
