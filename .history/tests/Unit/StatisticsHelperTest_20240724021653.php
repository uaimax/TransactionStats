<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class StatisticsHelperTest extends TestCase
{
    private $statisticsHelper;

    public function setUp(): void
    {
        parent::setUp();
        // Resetar a instância singleton e a memória compartilhada
        $this->statisticsHelper = StatisticsHelper::getInstance();
        $this->statisticsHelper->reset();
    }

    public function test_add_transaction()
    {
        $timestamp = Carbon::now();
        $this->statisticsHelper->addTransaction(100.50, $timestamp);

        $this->assertEquals(1, $this->statisticsHelper->getCount());
        $this->assertEquals(100.50, $this->statisticsHelper->getSum());
        $this->assertEquals(100.50, $this->statisticsHelper->getMin());
        $this->assertEquals(100.50, $this->statisticsHelper->getMax());

    }

    // Conferindo a listagem correta de estatísticas
    public function test_get_statistics()
    {
        $timestamp = Carbon::now();
        
        $this->statisticsHelper->addTransaction(100.50, $timestamp);
        $this->statisticsHelper->addTransaction(200.75, $timestamp->copy()->subSeconds(30));

        $statistics = $this->statisticsHelper->getStatistics();


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
    
        // Transação que será descartada por ter mais de 60 segundos
        $this->statisticsHelper->addTransaction(100.50, $timestamp->copy()->subSeconds(61));
    
        // Transação a ser considerada, porque está dentro do prazo.
        $this->statisticsHelper->addTransaction(200.75, $timestamp->copy()->subSeconds(30));
    
        // Forçar a limpeza de transações antigas
        $expireTime = Carbon::now()->subSeconds(60);
        $this->statisticsHelper->cleanUpOldTransactions($expireTime);
    
        // Carregar novamente as transações da memória compartilhada após a limpeza
        $this->statisticsHelper->loadTransactionsFromMemory();
    
        $statistics = $this->statisticsHelper->getStatistics();
    
        // Soma, visto que somente uma transação foi aceita
        $this->assertEquals(200.75, $statistics['sum']);
        $this->assertEquals(200.75, $statistics['avg']);
        $this->assertEquals(200.75, $statistics['max']);
        $this->assertEquals(200.75, $statistics['min']);
        $this->assertEquals(1, $statistics['count']);
    }

}
