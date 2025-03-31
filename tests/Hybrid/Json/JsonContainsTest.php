<?php

namespace SingleStore\Laravel\Tests\Hybrid\Json;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class JsonContainsTest extends BaseTest
{
    use HybridTestHelpers;

    /*
    |--------------------------------------------------------------------------
    | JSON contains string
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function json_contains_strings()
    {
        $query = DB::table('test')->whereJsonContains('data->array', 'en');

        $this->assertEquals(
            "select * from `test` where JSON_ARRAY_CONTAINS_JSON(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );

        $this->assertSame('"en"', $query->getBindings()[0]);

        if (! $this->runHybridIntegrations()) {
            return;
        }

        [$id1] = $this->insertJsonData([
            ['array' => ['en', 1, true, ['a' => 'b']]],
            ['array' => ['es', 2, false, ['c' => 'd']]],
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    #[Test]
    public function json_contains_double()
    {
        $query = DB::table('test')->whereJsonContains('data->array', 1.5);

        $this->assertEquals(
            "select * from `test` where JSON_ARRAY_CONTAINS_JSON(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );

        $this->assertSame('1.5', $query->getBindings()[0]);

        if (! $this->runHybridIntegrations()) {
            return;
        }

        [$id1] = $this->insertJsonData([
            ['array' => ['en', 1.5, true, ['a' => 'b']]],
            ['array' => ['es', 2.5, false, ['c' => 'd']]],
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    #[Test]
    public function json_contains_int()
    {
        $query = DB::table('test')->whereJsonContains('data->array', 1);

        $this->assertEquals(
            "select * from `test` where JSON_ARRAY_CONTAINS_JSON(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );

        $this->assertSame('1', $query->getBindings()[0]);

        if (! $this->runHybridIntegrations()) {
            return;
        }

        [$id1] = $this->insertJsonData([
            ['array' => ['en', 1, true, ['a' => 'b']]],
            ['array' => ['es', 2, false, ['c' => 'd']]],
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    #[Test]
    public function json_contains_bool()
    {
        $query = DB::table('test')->whereJsonContains('data->array', true);

        $this->assertEquals(
            "select * from `test` where JSON_ARRAY_CONTAINS_JSON(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );

        $this->assertSame('true', $query->getBindings()[0]);

        if (! $this->runHybridIntegrations()) {
            return;
        }

        [$id1] = $this->insertJsonData([
            ['array' => ['en', 1, true, ['a' => 'b']]],
            ['array' => ['es', 2, false, ['c' => 'd']]],
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    #[Test]
    public function json_contains_json()
    {
        $query = DB::table('test')->whereJsonContains('data->array', ['a' => 'b']);

        $this->assertEquals(
            "select * from `test` where JSON_ARRAY_CONTAINS_JSON(JSON_EXTRACT_JSON(data, 'array'), ?)",
            $query->toSql()
        );

        $this->assertEquals('{"a":"b"}', $query->getBindings()[0]);

        if (! $this->runHybridIntegrations()) {
            return;
        }

        [$id1] = $this->insertJsonData([
            ['array' => ['en', 1, true, ['a' => 'b']]],
            ['array' => ['es', 2, false, ['c' => 'd']]],
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }
}
