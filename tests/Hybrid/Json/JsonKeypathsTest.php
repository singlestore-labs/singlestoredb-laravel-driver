<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid\Json;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class JsonKeypathsTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function it_compiles_column_only_without_path()
    {
        $query = DB::table('test')->where('data', '[]');

        $this->assertEquals(
            'select * from `test` where `data` = ?',
            $query->toSql()
        );

        if (! $this->runHybridIntegrations()) {
            return;
        }

        [$id1] = $this->insertJsonData([[
            //
        ], [
            1,
        ]]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    /** @test */
    public function it_compiles_nested_json_path()
    {
        $query = DB::table('test')->where('data->value1->value2->value3->value4', 2);

        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_STRING(data, 'value1', 'value2', 'value3', 'value4') = ?",
            $query->toSql()
        );

        if (! $this->runHybridIntegrations()) {
            return;
        }

        [, $id2] = $this->insertJsonData([[
            'value1' => ['value2' => ['value3' => ['value4' => 1]]],
        ], [
            'value1' => ['value2' => ['value3' => ['value4' => 2]]],
        ], [
            'value1' => ['value2' => ['value3' => ['value4' => 3]]],
        ]]);

        $this->assertEquals($id2, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }

    /** @test */
    public function it_compiles_nested_json_path_with_array_access()
    {
        $query = DB::table('test')->where('data->value1[0]->value2[2][0]', 1);

        $this->assertEquals(
            "select * from `test` where JSON_EXTRACT_STRING(data, 'value1', 0, 'value2', 2, 0) = ?",
            $query->toSql()
        );

        if (! $this->runHybridIntegrations()) {
            return;
        }

        [$id1] = $this->insertJsonData([[
            'value1' => [['value2' => [[], [], [1]]]],
        ], [
            'value1' => [['value2' => [[], [], [2]]]],
        ]]);

        $this->assertEquals($id1, $query->first()->id);
        $this->assertEquals(1, $query->count());
    }
}
