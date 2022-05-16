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
    public function set_boolean()
    {
        [$logs] = DB::pretend(function ($database) {
            $database->table('test')->update([
                Json::JSON('data->bar[0]->baz') => true
            ]);
        });

        $this->assertEquals(
            "update `test` set data = JSON_SET_JSON(data, 'bar', 0, 'baz', 'true')",
            $logs['query']
        );
    }

    /** @test */
    public function set_string()
    {
        [$logs] = DB::pretend(function ($database) {
            $database->table('test')->update([
                Json::STRING('data->bar[0]->baz') => "foo"
            ]);
        });

        $this->assertEquals(
            "update `test` set data = JSON_SET_STRING(data, 'bar', 0, 'baz', ?)",
            $logs['query']
        );

        $this->assertSame(
            "foo",
            $logs['bindings'][0]
        );
    }


    /** @test */
    public function set_double()
    {
        [$logs] = DB::pretend(function ($database) {
            $database->table('test')->update([
                Json::DOUBLE('data->bar[0]->baz') => 1.3
            ]);
        });

        $this->assertEquals(
            "update `test` set data = JSON_SET_DOUBLE(data, 'bar', 0, 'baz', ?)",
            $logs['query']
        );

        $this->assertSame(
            1.3,
            $logs['bindings'][0]
        );
    }

    /** @test */
    public function set_bigint()
    {
        [$logs] = DB::pretend(function ($database) {
            $database->table('test')->update([
                Json::BIGINT('data->bar[0]->baz') => 10
            ]);
        });

        $this->assertEquals(
            "update `test` set data = JSON_SET_BIGINT(data, 'bar', 0, 'baz', ?)",
            $logs['query']
        );

        $this->assertSame(
            10,
            $logs['bindings'][0]
        );
    }

    /** @test */
    public function set_json()
    {
        [$logs] = DB::pretend(function ($database) {
            $database->table('test')->update([
                Json::JSON('data->bar[0]->baz') => ['foo' => 'bar']
            ]);
        });

        $this->assertEquals(
        // @TODO is cast as json right?
            "update `test` set data = JSON_SET_JSON(data, 'bar', 0, 'baz', cast(? as json))",
            $logs['query']
        );

        $this->assertSame(
            '{"foo":"bar"}',
            $logs['bindings'][0]
        );
    }


    /** @test */
    public function must_provide_a_type()
    {
        $this->expectException(SingleStoreDriverException::class);
        $this->expectExceptionMessage(
            'You must provide a JSON type when performing an update. Please use one of the Json::[TYPE] methods.'
        );

        DB::pretend(function ($database) {
            $database->table('test')->update([
                'data->bar[0]->baz' => 10
            ]);
        });
    }

    /** @test */
    public function must_provide_a_valid_type()
    {
        $this->expectException(SingleStoreDriverException::class);
        $this->expectExceptionMessage(
            'Unknown JSON type "FIZ"'
        );

        DB::pretend(function ($database) {
            $database->table('test')->update([
                Json::wrap('FIZ', 'data->bar[0]->baz') => 10
            ]);
        });
    }
}
