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

    public function testReset()
    {
        // Chame o método reset
        $this->statisticsHelper->reset();

        // Verifique se as transações e estatísticas estão vazias
        $this->assertEmpty($this->statisticsHelper->transactions);
        $this->assertEmpty($this->statisticsHelper->statistics);
    }

    public function testAddTransaction()
    {
        // Adicione uma transação
        $response = $this->statisticsHelper->addTransaction(100.0, Carbon::now()->timestamp);

        // Verifique se a resposta é 201 (criada com sucesso)
        $this->assertEquals(201, $response);

        // Verifique se a transação foi adicionada corretamente
        $transactions = $this->statisticsHelper->transactions;
        $this->assertNotEmpty($transactions);
        $this->assertCount(1, $transactions);
        $this->assertEquals(100.0, $transactions[array_key_first($transactions)][0]['amount']);
    }
}
