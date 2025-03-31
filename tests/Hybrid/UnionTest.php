<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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

    #[Test]
    public function union_all()
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

    #[Test]
    public function union_with_order_by_limit_and_offset()
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

    #[Test]
    public function union_with_order_by()
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

    #[Test]
    public function union_with_limit()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $first = DB::table('test')->where('id', '<', 3);
        $second = DB::table('test')->where('id', '>', 5);
        $res = $first->union($second)->limit(2)->get();

        $this->assertCount(2, $res);
    }

    #[Test]
    public function union_with_offset()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $first = DB::table('test')->where('id', '<', 3);
        $second = DB::table('test')->where('id', '>', 5);
        $res = $first->union($second)->offset(1)->get();

        $this->assertCount(2, $res);
    }

    #[Test]
    public function union_with_inner_offset()
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
