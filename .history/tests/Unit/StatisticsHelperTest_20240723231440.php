<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class StatisticsHelperTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_add_transaction()
    {
        $timestamp = Carbon::now();
        StatisticsHelper::addTransaction(100.50, $timestamp);

        $this->assertEquals(1, StatisticsHelper::getCount());
        $this->assertEquals(100.50, StatisticsHelper::getSum());
        $this->assertEquals(100.50, StatisticsHelper::getMin());
        $this->assertEquals(100.50, StatisticsHelper::getMax());

    }

    // Conferindo a listagem correta de estatísticas
    public function test_get_statistics()
    {
        $timestamp = Carbon::now();

        StatisticsHelper::addTransaction(100.50, $timestamp);
        StatisticsHelper::addTransaction(200.75, $timestamp->copy()->subSeconds(30));

        $statistics = StatisticsHelper::getStatistics();

        // Segue o que teste espera receber
        $this->assertEquals(301.25, $statistics['sum']);
        $this->assertEquals(150.63, $statistics['avg']);
        $this->assertEquals(200.75, $statistics['max']);
        $this->assertEquals(100.50, $statistics['min']);
        $this->assertEquals(2, $statistics['count']);
    }
    
    public function test_cleanup_old_transactions()
    {
        $timestamp = Carbon::now();
        try {
            // Transação que será descartada por ter mais de 60 segundos
            StatisticsHelper::addTransaction(100.50, $timestamp->copy()->subSeconds(61));

        } catch (InvalidArgumentException $e) {
            $this->assertEquals('Transação tem mais de 60 segundos', $e->getMessage());
        }
        
        // Transação a ser considerada, porque está dentro do prazo.
        StatisticsHelper::addTransaction(200.75, $timestamp->copy()->subSeconds(30));

        $statistics = StatisticsHelper::getStatistics();

        // Soma, visto que somente uma transação foi aceita
        $this->assertEquals(200.75, $statistics['sum']);
        $this->assertEquals(200.75, $statistics['avg']);
        $this->assertEquals(200.75, $statistics['max']);
        $this->assertEquals(200.75, $statistics['min']);
        $this->assertEquals(1, $statistics['count']);
    }
}
