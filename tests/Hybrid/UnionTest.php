<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;

class UnionTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    function union() {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
        });

        DB::table('test')->insert([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
            ['id' => 100],
        ]);

        $first = DB::table('test')->where('id', '<', 3);
        $second = DB::table('test')->where('id', '>', 5);
        $res = $first->union($second)->get();

        $indexes = array_map(function ($value): int {
            return $value->id;
        }, $res->toArray());
        sort($indexes);

        $this->assertEquals([1, 2, 100], $indexes);
    }

    /** @test */
    function unionWithOrderByLimitAndOffset() {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
        });

        DB::table('test')->insert([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
            ['id' => 100],
        ]);

        $first = DB::table('test')->where('id', '<', 3);
        $second = DB::table('test')->where('id', '>', 5);
        $res = $first->union($second)->orderBy('id')->limit(1)->offset(1)->get();

        $indexes = array_map(function ($value): int {
            return $value->id;
        }, $res->toArray());
        sort($indexes);

        $this->assertEquals([2], $indexes);
    }

}