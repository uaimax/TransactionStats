<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class StatisticsHelperTest extends TestCase
{
    protected $statisticsHelper;

    // Esta função é executada antes de cada teste para configurar o ambiente de teste.
    public function setUp(): void
    {
        // Chamamos a função setUp da classe pai (TestCase) para garantir que qualquer configuração
        // adicional necessária pela estrutura de testes seja realizada.
        parent::setUp();

        // Criamos uma nova instância de StatisticsHelper para cada teste.
        $this->statisticsHelper = new StatisticsHelper();

        // Resetamos o estado da instância, limpando qualquer dado ou estado anterior.
        // Isso garante que cada teste comece com um estado limpo e previsível.
        $this->statisticsHelper->reset();
    }

    public function test_add_transaction_helper()
    {
        // Criando um timestamp Carbon para usar no teste
        $timestamp = Carbon::now()->subSeconds(30); // 30 segundos atrás

        $this->statisticsHelper->addTransaction(100.50, $timestamp);

        // Obtendo as estatísticas
        $statistics = $this->statisticsHelper->getStatistics();

        // Verificando as estatísticas
        $this->assertEquals(1, $statistics['count']);
        $this->assertEquals(100.50, $statistics['sum']);
        $this->assertEquals(100.50, $statistics['min']);
        $this->assertEquals(100.50, $statistics['max']);

    }

    // Conferindo a listagem correta de estatísticas
    public function test_get_statistics_helper()
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
    
        $expireTime = Carbon::now()->subSeconds(60);
        $this->statisticsHelper->cleanUpOldTransactions($expireTime);
    
        $statistics = $this->statisticsHelper->getStatistics();
    
        $this->assertEquals(200.75, $statistics['sum']);
        $this->assertEquals(200.75, $statistics['avg']);
        $this->assertEquals(200.75, $statistics['max']);
        $this->assertEquals(200.75, $statistics['min']);
        $this->assertEquals(1, $statistics['count']);
    }

}
