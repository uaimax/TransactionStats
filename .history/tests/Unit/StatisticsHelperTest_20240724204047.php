<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use PHPUnit\Framework\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StatisticsHelperTest extends TestCase
{

    protected $statisticsHelper;

    public function setUp(): void
    {
        parent::setUp();

        // Mock the Log facade
        Log::shouldReceive('info')->andReturnNull();
        
        $this->statisticsHelper = new StatisticsHelper();
        $this->statisticsHelper->reset();
    }

    public function testReset()
    {
        $this->statisticsHelper->reset();

        $this->assertEmpty($this->statisticsHelper->getTransactions());
        $this->assertEmpty($this->statisticsHelper->getStatisticsData());
    }

    public function testAddTransaction()
    {
        $response = $this->statisticsHelper->addTransaction(100.0, Carbon::now()->timestamp);

        $this->assertEquals(201, $response);

        $transactions = $this->statisticsHelper->getTransactions();
        $this->assertNotEmpty($transactions);
        $this->assertCount(1, $transactions[array_key_first($transactions)]);
        $this->assertEquals(100.0, $transactions[array_key_first($transactions)][0]['amount']);
    }
}
