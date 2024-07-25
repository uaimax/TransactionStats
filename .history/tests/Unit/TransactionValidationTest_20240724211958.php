<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use PHPUnit\Framework\TestCase;
use Carbon\Carbon;

class TransactionValidationTest extends TestCase
{
    public function testValidTransaction()
    {
        $helper = new StatisticsHelper();

        // Adicione uma transação válida
        $response = $helper->addTransaction(100.0, Carbon::now()->timestamp);

        // Verifique se a resposta é 201 (criada com sucesso)
        $this->assertEquals(201, $response);
    }

    public function testOldTransaction()
    {
        $helper = new StatisticsHelper();

        // Adicione uma transação antiga
        $timestamp = Carbon::now()->subMinutes(2)->timestamp;
        $response = $helper->addTransaction(100.0, $timestamp);

        // Verifique se a resposta é 204 (transação muito antiga)
        $this->assertEquals(204, $response);
    }

    public function testFutureTransaction()
    {
        $helper = new StatisticsHelper();

        // Adicione uma transação com timestamp no futuro
        $timestamp = Carbon::now()->addMinutes(1)->timestamp;
        $response = $helper->addTransaction(100.0, $timestamp);

        // Verifique se a resposta é 422 (timestamp no futuro)
        $this->assertEquals(422, $response);
    }
}
