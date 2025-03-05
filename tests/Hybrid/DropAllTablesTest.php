<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Facades\Schema;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;
use PHPUnit\Framework\Attributes\Test;

class DropAllTablesTest extends BaseTest
{
    use HybridTestHelpers;

    #[Test]
    public function it_drops_all_tables_sequentially()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        $this->mockDatabaseConnection = false;

        Schema::dropIfExists('test_drop_1');
        Schema::dropIfExists('test_drop_2');

        $this->assertFalse(Schema::hasTable('test_drop_1'));
        $this->assertFalse(Schema::hasTable('test_drop_2'));

        Schema::create('test_drop_1', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('test_drop_2', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue(Schema::hasTable('test_drop_1'));
        $this->assertTrue(Schema::hasTable('test_drop_2'));

        $this->getConnection()->getSchemaBuilder()->dropAllTables();
    }
}
