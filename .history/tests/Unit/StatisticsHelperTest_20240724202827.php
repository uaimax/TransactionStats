<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use PHPUnit\Framework\TestCase;

class StatisticsHelperTest extends TestCase
{
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
