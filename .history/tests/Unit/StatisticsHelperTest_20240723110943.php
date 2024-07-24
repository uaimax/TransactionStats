<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class StatisticsHelperTest extends TestCase
{
    public function setUp(): void
    {
        StatisticsHelper::reset();
    }

    public function test_add_transaction()
    {
        $timestamp = Carbon::now();
        StatisticsHelper::addTransaction(100.50, $timestamp);

        $this->assertEquals(1, StatisticsHelper::getCount());
        $this->assertEquals(100.50, StatisticsHelper::getSum());
        $this->assertEquals(100.50, StatisticsHelper::getMin());
        $this->assertEquals(100.50, StatisticsHelper::getSum());

    }
}
