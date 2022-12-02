<?php

namespace SingleStore\Laravel\Tests\Hybrid\Json;

use Illuminate\Foundation\Application;
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
        if (version_compare(Application::VERSION, '8.79.0', '<=')) {
            // fulltext not added until later on in laravel 8 releases
            $this->markTestSkipped('requires higher laravel version');

            return;
        }

        $query = DB::table('test')->whereFullText('title', 'performance');

        $this->assertEquals(
            'select * from `test` where MATCH (`title`) AGAINST (?)',
            $query->toSql()
        );

        $this->assertSame('performance', $query->getBindings()[0]);

        if (! $this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
            $table->text('title')->collation('utf8_unicode_ci');

            $table->fullText(['title']);
        });

        DB::table('test')->insert([[
            'title' => 'Designing Data-Intensive Applications: The Big Ideas Behind Reliable, Scalable, and Maintainable Systems',
        ], [
            'title' => 'Data Pipelines Pocket Reference: Moving and Processing Data for Analytics',
        ], [
            'title' => 'Data Quality Fundamentals',
        ], [
            'title' => 'High Performance MySQL: Optimization, Backups, and Replication',
        ]]);

        // Force the index to be updated immediately, as it may happen async a little later
        DB::statement('OPTIMIZE TABLE test FLUSH');

        $this->assertSame(
            'High Performance MySQL: Optimization, Backups, and Replication',
            $query->get()[0]->title
        );
    }

    /** @test */
    public function fulltext_multicolumn()
    {
        if (version_compare(Application::VERSION, '8.79.0', '<=')) {
            // fulltext not added until later on in laravel 8 releases
            $this->markTestSkipped('requires higher laravel version');

            return;
        }

        $query = DB::table('test')->whereFullText(['name', 'race'], 'Laika');

        $this->assertEquals(
            'select * from `test` where MATCH (`name`, `race`) AGAINST (?)',
            $query->toSql()
        );

        $this->assertSame('Laika', $query->getBindings()[0]);

        if (! $this->runHybridIntegrations()) {
            return;
        }

        $this->createTable(function (Blueprint $table) {
            $table->id();
            $table->text('name')->collation('utf8_unicode_ci');
            $table->text('race')->collation('utf8_unicode_ci');

            $table->fullText(['name', 'race']);
        });

        DB::table('test')->insert([[
            'name' => 'Laika',
            'race' => 'Dog',
        ], [
            'name' => 'Ham',
            'race' => 'Monkey',
        ]]);

        // Force the index to be updated immediately, as it may happen async a little later
        DB::statement('OPTIMIZE TABLE test FLUSH');

        $this->assertSame('Laika', $query->get()[0]->name);
    }
}
