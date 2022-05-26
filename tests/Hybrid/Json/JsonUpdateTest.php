<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid\Json;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Exceptions\SingleStoreDriverException;
use SingleStore\Laravel\Exceptions\UnsupportedFunctionException;
use SingleStore\Laravel\Query\Json;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class JsonUpdateTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function set_boolean_syntax()
    {
        [$logs] = DB::pretend(function ($database) {
            $database->table('test')->update([
                'data->value1' => true
            ]);
        });

        $this->assertEquals(
            "update `test` set data = JSON_SET_JSON(data, 'value1', 'true')",
            $logs['query']
        );
    }

    /** @test */
    public function set_boolean_execution()
    {
        if (!$this->runHybridIntegrations()) {
            return;
        }

        $this->insertJsonData([
            ['value1' => false],
        ]);

        $this->assertEquals(1, DB::table('test')->where('data->value1', false)->count());

        DB::table('test')->update([
            'data->value1' => true
        ]);

        $this->assertEquals(0, DB::table('test')->where('data->value1', false)->count());
        $this->assertEquals(1, DB::table('test')->where('data->value1', true)->count());
    }

    /** @test */
    public function set_string_syntax()
    {
        [$logs] = DB::pretend(function ($database) {
            $database->table('test')->update([
                'data->value1' => "foo"
            ]);
        });

        $this->assertEquals(
            "update `test` set data = JSON_SET_JSON(data, 'value1', ?)",
            $logs['query']
        );

        $this->assertSame('"foo"', $logs['bindings'][0]);
    }

    /** @test */
    public function set_string_execution()
    {
        if (!$this->runHybridIntegrations()) {
            return;
        }

        $this->insertJsonData([
            ['value1' => "foo"],
        ]);

        $this->assertEquals(1, DB::table('test')->where('data->value1', "foo")->count());

        DB::table('test')->update([
            'data->value1' => "bar"
        ]);

        $this->assertEquals(0, DB::table('test')->where('data->value1', "foo")->count());
        $this->assertEquals(1, DB::table('test')->where('data->value1', "bar")->count());
    }


    /** @test */
    public function set_double_syntax()
    {
        [$logs] = DB::pretend(function ($database) {
            $database->table('test')->update([
                'data->value1' => 1.3
            ]);
        });

        $this->assertEquals(
            "update `test` set data = JSON_SET_JSON(data, 'value1', ?)",
            $logs['query']
        );

        $this->assertSame(1.3, $logs['bindings'][0]);
    }

    /** @test */
    public function set_double_execution()
    {
        if (!$this->runHybridIntegrations()) {
            return;
        }

        $this->insertJsonData([
            ['value1' => 1.3],
        ]);

        $this->assertEquals(1, DB::table('test')->where('data->value1', 1.3)->count());

        DB::table('test')->update([
            'data->value1' => 1.5
        ]);

        $this->assertEquals(0, DB::table('test')->where('data->value1', 1.3)->count());
        $this->assertEquals(1, DB::table('test')->where('data->value1', 1.5)->count());
    }

    /** @test */
    public function set_json_syntax()
    {
        [$logs] = DB::pretend(function ($database) {
            $database->table('test')->update([
                'data->value1' => ['foo' => 'bar']
            ]);
        });

        $this->assertEquals(
            "update `test` set data = JSON_SET_JSON(data, 'value1', ?)",
            $logs['query']
        );

        $this->assertSame('{"foo":"bar"}', $logs['bindings'][0]);
    }

    /** @test */
    public function set_json_execution()
    {
        if (!$this->runHybridIntegrations()) {
            return;
        }

        $this->insertJsonData([
            ['value1' => ['foo' => 'bar']],
        ]);

        $this->assertEquals(1, DB::table('test')->where('data->value1', json_encode(['foo' => 'bar']))->count());

        DB::table('test')->update([
            'data->value1' => ["foo" => "baz"]
        ]);

        $this->assertEquals(0, DB::table('test')->where('data->value1', json_encode(['foo' => 'bar']))->count());

        $this->assertEquals(1, DB::table('test')->where('data->value1', json_encode(['foo' => 'baz']))->count());
    }

}
