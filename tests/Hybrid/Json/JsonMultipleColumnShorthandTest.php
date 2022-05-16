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

class JsonMultipleColumnShorthandTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function supports_columns_as_array_style()
    {
        // The particular type (bigint) doesn't matter here, we're just testing that the method works at all.
        $query = DB::table('test')->whereJsonBigint([
            'data->value1' => 1,
            'data->value2' => 2,
        ]);

        $this->assertEquals(
            "select * from `test` where (JSON_EXTRACT_BIGINT(data, 'value1') = ? and JSON_EXTRACT_BIGINT(data, 'value2') = ?)",
            $query->toSql()
        );
    }

    /** @test */
    public function supports_columns_as_array_style_wrapped_takes_priority()
    {
        $query = DB::table('test')->whereJsonBigint([
            Json::STRING('data->value1') => 1,
            'data->value2' => 2,
        ]);

        $this->assertEquals(
            "select * from `test` where (JSON_EXTRACT_STRING(data, 'value1') = ? and JSON_EXTRACT_BIGINT(data, 'value2') = ?)",
            $query->toSql()
        );
    }
}
