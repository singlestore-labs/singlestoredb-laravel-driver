<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Facades\DB;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use PHPUnit\Framework\Attributes\Test;

class OptionTest extends BaseTest
{
    use HybridTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->runHybridIntegrations()) {
            $this->createTable(function (Blueprint $table) {
                $table->id();
            });
        }
    }

    #[Test]
    public function single_option()
    {
        $query = DB::table('test')->options(['interpreter_mode' => 'compile']);
        echo get_class($query);
        $this->assertEquals('select * from `test` OPTION (interpreter_mode=compile)', $query->toSql());

        if ($this->runHybridIntegrations()) {
            $query->get();
        }
    }

    #[Test]
    public function empty_option()
    {
        $query = DB::table('test')->options([]);
        echo get_class($query);
        $this->assertEquals('select * from `test`', $query->toSql());

        if ($this->runHybridIntegrations()) {
            $query->get();
        }
    }

    #[Test]
    public function multi_option()
    {
        $query = DB::table('test')->options(['interpreter_mode' => 'compile', 'resource_pool' => 'default_pool']);
        echo get_class($query);
        $this->assertEquals('select * from `test` OPTION (interpreter_mode=compile,resource_pool=default_pool)', $query->toSql());

        if ($this->runHybridIntegrations()) {
            $query->get();
        }
    }
}
