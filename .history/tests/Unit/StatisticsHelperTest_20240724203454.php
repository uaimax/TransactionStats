<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use PHPUnit\Framework\TestCase;
use Carbon\Carbon;

class StatisticsHelperTest extends TestCase
{
    protected $statisticsHelper;

    public function setUp(): void
    {
        parent::setUp();
        $this->statisticsHelper = new StatisticsHelper();
        $this->statisticsHelper->reset();
    }

    private function getPrivateProperty($object, $propertyName)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    public function testReset()
    {
        $this->statisticsHelper->reset();

        $transactions = $this->getPrivateProperty($this->statisticsHelper, 'transactions');
        $statistics = $this->getPrivateProperty($this->statisticsHelper, 'statistics');

        $this->assertEmpty($transactions);
        $this->assertEmpty($statistics);
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
