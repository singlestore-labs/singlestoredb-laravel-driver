<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;

class UnionTest extends BaseTest
{
    use HybridTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->runHybridIntegrations()) {
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
        }
    }

    /** @test */
    public function union()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

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
    public function unionAll()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $first = DB::table('test')->where('id', '<', 4);
        $second = DB::table('test')->where('id', '>', 2);
        $res = $first->unionAll($second)->get();

        $indexes = array_map(function ($value): int {
            return $value->id;
        }, $res->toArray());
        sort($indexes);

        $this->assertEquals([1, 2, 3, 3, 4, 100], $indexes);
    }

    /** @test */
    public function unionWithOrderByLimitAndOffset()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $first = DB::table('test')->where('id', '<', 3);
        $second = DB::table('test')->where('id', '>', 5);
        $res = $first->union($second)->orderBy('id')->limit(1)->offset(1)->get();

        $indexes = array_map(function ($value): int {
            return $value->id;
        }, $res->toArray());

        $this->assertEquals([2], $indexes);
    }

    /** @test */
    public function unionWithOrderBy()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $first = DB::table('test')->where('id', '<', 3);
        $second = DB::table('test')->where('id', '>', 5);
        $res = $first->union($second)->orderBy('id')->get();

        $indexes = array_map(function ($value): int {
            return $value->id;
        }, $res->toArray());

        $this->assertEquals([1, 2, 100], $indexes);
    }

    /** @test */
    public function unionWithLimit()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $first = DB::table('test')->where('id', '<', 3);
        $second = DB::table('test')->where('id', '>', 5);
        $res = $first->union($second)->limit(2)->get();

        $this->assertCount(2, $res);
    }

    /** @test */
    public function unionWithOffset()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $first = DB::table('test')->where('id', '<', 3);
        $second = DB::table('test')->where('id', '>', 5);
        $res = $first->union($second)->offset(1)->get();

        $this->assertCount(2, $res);
    }

    /** @test */
    public function unionWithInnerOffset()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $first = DB::table('test')->where('id', '<', 3)->offset(1)->orderBy('id');
        $second = DB::table('test')->where('id', '>', 5);
        $res = $first->union($second)->get();

        $indexes = array_map(function ($value): int {
            return $value->id;
        }, $res->toArray());
        sort($indexes);

        $this->assertEquals([2, 100], $indexes);
    }
}
