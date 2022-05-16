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

class JsonQueryTypesTest extends BaseTest
{
    use HybridTestHelpers;

//    /** @test */
//    public function supports_multiple_typed_columns()
//    {
//        $query = DB::table('test')->where([
//            'data->value1' => 1,
//            'data->value2' => 2,
//        ]);
//
//        $this->assertEquals(
//            "",
//            $query->toSql()
//        );
//    }

    /** @test */
    public function compile_json_bigint()
    {
        $query = DB::table('test')->whereJsonBigint('data->value1', 1);

        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_BIGINT(data, 'value1') = ?",
            $query->toSql()
        );

        $this->assertSame(1, $query->getBindings()[0]);

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1,] = $this->insertJsonData([
            ['value1' => ['value2' => 1]],
            ['value1' => ['value2' => 2]]
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    /** @test */
    public function compile_json_bigint_all_versions()
    {
        $query1 = DB::table('test')->whereJsonBigint('data->value1', 1);
        $query2 = DB::table('test')->whereJson(Json::BIGINT, 'data->value1', 1);
        $query3 = DB::table('test')->where(Json::BIGINT('data->value1'), 1);

        $this->assertSame($query1->toSql(), $query2->toSql());
        $this->assertSame($query1->toSql(), $query3->toSql());
    }

    /** @test */
    public function compile_json_double()
    {
        $query = DB::table('test')->whereJsonDouble('data->value1', 1.5);

        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_DOUBLE(data, 'value1') = ?",
            $query->toSql()
        );

        $this->assertSame(1.5, $query->getBindings()[0]);

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1,] = $this->insertJsonData([
            ['value1' => ['value2' => 1.5]],
            ['value1' => ['value2' => 2.5]]
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    /** @test */
    public function compile_json_double_all_versions()
    {
        $query1 = DB::table('test')->whereJsonDouble('data->value1', 1);
        $query2 = DB::table('test')->whereJson(Json::DOUBLE, 'data->value1', 1);
        $query3 = DB::table('test')->where(Json::DOUBLE('data->value1'), 1);

        $this->assertSame($query1->toSql(), $query2->toSql());
        $this->assertSame($query1->toSql(), $query3->toSql());
    }

    /** @test */
    public function compile_json_string()
    {
        $query = DB::table('test')->whereJsonString('data->value1', 'foo');

        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_STRING(data, 'value1') = ?",
            $query->toSql()
        );

        $this->assertSame('foo', $query->getBindings()[0]);

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1,] = $this->insertJsonData([
            ['value1' => 'foo'],
            ['value1' => 'bar']
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    /** @test */
    public function compile_json_string_all_versions()
    {
        $query1 = DB::table('test')->whereJsonString('data->value1', 1);
        $query2 = DB::table('test')->whereJson(Json::STRING, 'data->value1', 1);
        $query3 = DB::table('test')->where(Json::STRING('data->value1'), 1);

        $this->assertSame($query1->toSql(), $query2->toSql());
        $this->assertSame($query1->toSql(), $query3->toSql());
    }

    /** @test */
    public function compile_json_json()
    {
        $query = DB::table('test')->whereJsonJson('data->value1', json_encode(['value2' => 2]));

        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_JSON(data, 'value1') = ?",
            $query->toSql()
        );

        $this->assertSame('{"value2":2}', $query->getBindings()[0]);

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1,] = $this->insertJsonData([
            ['value1' => ['value2' => 1]],
            ['value1' => ['value2' => 2]]
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    /** @test */
    public function compile_json_json_all_versions()
    {
        $query1 = DB::table('test')->whereJsonJson('data->value1', 1);
        $query2 = DB::table('test')->whereJson(Json::JSON, 'data->value1', 1);
        $query3 = DB::table('test')->where(Json::JSON('data->value1'), 1);

        $this->assertSame($query1->toSql(), $query2->toSql());
        $this->assertSame($query1->toSql(), $query3->toSql());
    }

    /** @test * */
    public function compile_json_boolean()
    {
        $query = DB::table('test')->where('data->value1', true);

        // @TODO Carl: is this right?
        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_DOUBLE(data, 'value1') = true",
            $query->toSql()
        );

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1,] = $this->insertJsonData([
            ['value1' => true],
            ['value1' => false]
        ]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

//    /** @test */
//    public function compile_json()
//    {
//        // JSON_EXTRACT_DOUBLE
//        // JSON_EXTRACT_STRING
//        // JSON_EXTRACT_JSON
//        // JSON_EXTRACT_BIGINT
//
//        // GEOGRAPHY_INTERSECTS ( geo1, geo2 )
//        // GEOGRAPHY_WITHIN_DISTANCE("POINT(-73.94990499 40.69150746)", shape, 10000);
//
//    }

    /** @test */
    public function updates_a_json_value()
    {

//        $this->createTable(function (Blueprint $table) {
//            $table->id();
//            $table->json('data');
//        });
//
//        DB::table('test')->insert([
//            'data' => json_encode([
//                'enabled' => false
//            ])
//        ]);

//        DB::table('test')->where('id', 1)->update([
//            'data->bar[0]->baz' => true
//        ]);
    }
}
