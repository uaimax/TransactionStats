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
        $helper = new StatisticsHelper();

        // Chame o método reset
        $helper->reset();

        // Verifique se as transações e estatísticas estão vazias
        $this->assertEmpty($helper->transactions);
        $this->assertEmpty($helper->statistics);
    }

    public function testAddTransaction()
    {
        $helper = new StatisticsHelper();

        // Adicione uma transação
        $response = $helper->addTransaction(100.0, time());

        // Verifique se a resposta é 201 (criada com sucesso)
        $this->assertEquals(201, $response);
    }
}
