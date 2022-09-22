<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SingleStore\Laravel\Tests\Hybrid\Json;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class FulltextTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function fulltext()
    {
        $query = DB::table('test')->whereFullText('first_name', 'aaron');

        $this->assertEquals(
            'select * from `test` where MATCH (`first_name`) AGAINST (?)',
            $query->toSql()
        );

        $this->assertSame('aaron', $query->getBindings()[0]);

        if (! $this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
            $table->text('first_name');

            $table->fullText(['first_name']);
        });

        DB::table('test')->insert([[
            'first_name' => 'aaron',
        ], [
            'first_name' => 'taylor',
        ]]);

        // @TODO Assert query returns
    }

    /** @test */
    public function fulltext_multicolumn()
    {
        $query = DB::table('test')->whereFullText(['first_name', 'last_name'], 'aaron');

        $this->assertEquals(
            'select * from `test` where MATCH (`first_name`, `last_name`) AGAINST (?)',
            $query->toSql()
        );

        $this->assertSame('aaron', $query->getBindings()[0]);
    }
}
