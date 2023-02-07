<?php

namespace SingleStore\Laravel\Tests\Hybrid\Json;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class JsonWhereTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function compile_unknown_as_string()
    {
        $query = DB::table('test')->where('data->value1->value2', 1);

        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_STRING(data, 'value1', 'value2') = ?",
            $query->toSql()
        );

        $this->assertSame(1, $query->getBindings()[0]);
    }

    /** @test */
    public function singlestore_will_cast_all_types()
    {
        $query1 = DB::table('test')->where('data->value1->value2', 'string');
        $query2 = DB::table('test')->where('data->value1->value2', 1);
        $query3 = DB::table('test')->where('data->value1->value2', 1.5);
        $query4 = DB::table('test')->where('data->value1->value2', json_encode(['a' => 'b']));

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1, $id2, $id3, $id4] = $this->insertJsonData([
            ['value1' => ['value2' => 'string']],
            ['value1' => ['value2' => 1]],
            ['value1' => ['value2' => 1.5]],
            ['value1' => ['value2' => ['a' => 'b']]],
        ]);

        $this->assertEquals($id1, $query1->first()->id);
        $this->assertEquals(1, $query1->count());

        $this->assertEquals($id2, $query2->first()->id);
        $this->assertEquals(1, $query2->count());

        $this->assertEquals($id3, $query3->first()->id);
        $this->assertEquals(1, $query3->count());

        $this->assertEquals($id4, $query4->first()->id);
        $this->assertEquals(1, $query4->count());
    }

    /** @test */
    public function json_boolean()
    {
        $query1 = DB::table('test')->where('data->value1->value2', true);
        $query2 = DB::table('test')->where('data->value1->value2', false);

        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_DOUBLE(data, 'value1', 'value2') = true",
            $query1->toSql()
        );

        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_DOUBLE(data, 'value1', 'value2') = false",
            $query2->toSql()
        );

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1, $id2] = $this->insertJsonData([
            ['value1' => ['value2' => true]],
            ['value1' => ['value2' => false]],
        ]);

        $this->assertEquals($id1, $query1->first()->id);
        $this->assertEquals(1, $query1->count());

        $this->assertEquals($id2, $query2->first()->id);
        $this->assertEquals(1, $query1->count());
    }

    /** @test */
    public function nested_where()
    {
        $query = DB::table('test')->where(function ($query) {
            $query->where('data->value1', 'foo')->orWhere(function ($query) {
                $query->where('data->value2', 1);
            });
        });

        $this->assertEquals(
            "select * from `test` where (JSON_EXTRACT_STRING(data, 'value1') = ? or (JSON_EXTRACT_STRING(data, 'value2') = ?))",
            $query->toSql()
        );
    }

    /** @test */
    public function where_null()
    {
        $query = DB::table('test')->whereNull('data->value1')->orderBy('id');

        $this->assertEquals(
            // @TODO check docs
            "select * from `test` where (JSON_EXTRACT_JSON(data, 'value1') IS NULL OR JSON_GET_TYPE(JSON_EXTRACT_JSON(data, 'value1')) = 'NULL') order by `id` asc",
            $query->toSql()
        );

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [, $id2, $id3] = $this->insertJsonData([
            ['value1' => ['value2' => 'string']],
            ['value1' => null],
            [null],
            ['value1' => ['value2' => 1]],
        ]);

        $this->assertEquals($id2, $query->get()[0]->id);
        $this->assertEquals($id3, $query->get()[1]->id);
    }

    /** @test */
    public function where_not_null()
    {
        $query = DB::table('test')->whereNotNull('data->value1')->orderBy('id');

        $this->assertEquals(
            // @TODO check docs
            "select * from `test` where (JSON_EXTRACT_JSON(data, 'value1') IS NOT NULL AND JSON_GET_TYPE(JSON_EXTRACT_JSON(data, 'value1')) != 'NULL') order by `id` asc",
            $query->toSql()
        );

        if (!$this->runHybridIntegrations()) {
            return;
        }

        [$id1,,, $id4] = $this->insertJsonData([
            ['value1' => ['value2' => 'string']],
            ['value1' => null],
            [null],
            ['value1' => ['value2' => 1]],
        ]);

        $this->assertEquals($id1, $query->get()[0]->id);
        $this->assertEquals($id4, $query->get()[1]->id);
    }
}
