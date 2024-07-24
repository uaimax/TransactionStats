<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
        Log::info('Linha 4: Início do teste', ['timestamp' => $timestamp->toDateTimeString()]);
        
        // Transação que será descartada por ter mais de 60 segundos
        $this->statisticsHelper->addTransaction(100.50, $timestamp->copy()->subSeconds(61));
        Log::info('Linha 7: Adicionada transação antiga', ['amount' => 100.50, 'timestamp' => $timestamp->copy()->subSeconds(61)->toDateTimeString()]);
        
        // Transação a ser considerada, porque está dentro do prazo.
        $this->statisticsHelper->addTransaction(200.75, $timestamp->copy()->subSeconds(30));
        Log::info('Linha 10: Adicionada transação recente', ['amount' => 200.75, 'timestamp' => $timestamp->copy()->subSeconds(30)->toDateTimeString()]);
        
        // Forçar a limpeza de transações antigas
        $expireTime = Carbon::now()->subSeconds(60);
        Log::info('Linha 13: Tempo de expiração para limpeza', ['expire_time' => $expireTime->toDateTimeString()]);
        $this->statisticsHelper->cleanUpOldTransactions($expireTime);
        Log::info('Linha 15: Limpeza de transações antigas forçada');
        
        $statistics = $this->statisticsHelper->getStatistics();
        Log::info('Linha 21: Estatísticas calculadas', ['statistics' => $statistics]);
        
        // Soma, visto que somente uma transação foi aceita
        $this->assertEquals(200.75, $statistics['sum']);
        Log::info('Linha 24: Asserção de soma', ['expected' => 200.75, 'actual' => $statistics['sum']]);
        
        $this->assertEquals(200.75, $statistics['avg']);
        Log::info('Linha 27: Asserção de média', ['expected' => 200.75, 'actual' => $statistics['avg']]);
        
        $this->assertEquals(200.75, $statistics['max']);
        Log::info('Linha 30: Asserção de máximo', ['expected' => 200.75, 'actual' => $statistics['max']]);
        
        $this->assertEquals(200.75, $statistics['min']);
        Log::info('Linha 33: Asserção de mínimo', ['expected' => 200.75, 'actual' => $statistics['min']]);
        
        $this->assertEquals(1, $statistics['count']);
        Log::info('Linha 36: Asserção de contagem', ['expected' => 1, 'actual' => $statistics['count']]);
    }
    

}
