<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;

class orderByTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function ignores_order_by_in_delete()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
        });

        DB::table('test')->insert([
            ['id' => 1],
        ]);

        DB::table('test')->orderBy('id', 'asc')->delete();
    }

    /** @test */
    public function ignores_order_by_in_update()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
            $table->string('a');
        });

        DB::table('test')->insert([
            ['id' => 1, 'a' => 'a'],
        ]);

        DB::table('test')->orderBy('id', 'asc')->update(['id' => 1, 'a' => 'b']);
    }
}
