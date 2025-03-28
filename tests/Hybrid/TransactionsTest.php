<?php

namespace SingleStore\Laravel\Tests\Hybrid;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use SingleStore\Laravel\Schema\Blueprint;
use SingleStore\Laravel\Tests\BaseTest;

class TransactionsTest extends BaseTest
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
    public function multiple_begin()
    {
        if (! $this->runHybridIntegrations()) {
            return;
        }

        DB::beginTransaction();
        DB::rollBack();
        DB::insert('select 1');
        DB::beginTransaction();
        DB::rollBack();
    }
}
