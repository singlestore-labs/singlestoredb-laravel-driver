<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Foundation\Application;
use SingleStore\Laravel\Tests\BaseTest;
use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\Hybrid\HybridTestHelpers;

class GroupLimitTest extends BaseTest
{
    use HybridTestHelpers;

    /** @test */
    public function groupLimit()
    {
        if (version_compare(Application::VERSION, '11.0.0', '<')) {
            // fulltext not added until later on in laravel 8 releases
            $this->markTestSkipped('requires higher laravel version');

            return;
        }

        $query = DB::table('test')->orderBy('id')->groupLimit(2, 'group');
        $this->assertEquals(
            'select * from (select *, row_number() over (partition by `group` order by `id` asc) as `laravel_row` from `test`) as `laravel_table` where `laravel_row` <= 2 order by `laravel_row`',
            $query->toSql()
        );

        if (!$this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
            $table->integer('group');
        });

        DB::table('test')->insert([
            ['id' => 1, 'group' => 1],
            ['id' => 2, 'group' => 1],
            ['id' => 3, 'group' => 1],
            ['id' => 4, 'group' => 2],
            ['id' => 5, 'group' => 3],
            ['id' => 6, 'group' => 3],
            ['id' => 7, 'group' => 3],
            ['id' => 8, 'group' => 3],
        ]);

        $ids = $query->get(['id'])->pluck('id')->toArray();
        sort($ids);
        $this->assertEquals($ids, [1, 2, 4, 5, 6]);
    }
}
