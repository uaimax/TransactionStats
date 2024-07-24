<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Tests\TestCase;

class StatisticsHelperTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        StatisticsHelper::reset();
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
    public function test_get_statistics_with_multiple_transactions(): void
    {
        // Adicionando transações válidas via API
        $response1 = $this->postJson('/api/transactions', ['amount' => 100.10, 'timestamp' => Carbon::now()->subSeconds(10)->format('Y-m-d\TH:i:s.v\Z')]);
        Log::info('Resposta da adição de transação 1', ['response' => $response1->getContent()]);
    
        $response2 = $this->postJson('/api/transactions', ['amount' => 100.75, 'timestamp' => Carbon::now()->subSeconds(20)->format('Y-m-d\TH:i:s.v\Z')]);
        Log::info('Resposta da adição de transação 2', ['response' => $response2->getContent()]);
    
        $response3 = $this->postJson('/api/transactions', ['amount' => 100.20, 'timestamp' => Carbon::now()->subSeconds(30)->format('Y-m-d\TH:i:s.v\Z')]);
        Log::info('Resposta da adição de transação 3', ['response' => $response3->getContent()]);
    
        $response4 = $this->postJson('/api/transactions', ['amount' => 300.25, 'timestamp' => Carbon::now()->subSeconds(40)->format('Y-m-d\TH:i:s.v\Z')]);
        Log::info('Resposta da adição de transação 4', ['response' => $response4->getContent()]);
    
        Log::info('Verificando as estatísticas via API.');
    
        // Verificando estatísticas
        $response = $this->getJson('/api/statistics');
        Log::info('Estatísticas atuais: ' . $response->getContent());
    
        $response->assertStatus(200)->assertJson([
            'sum' => 601.30,
            'avg' => 150.33,
            'max' => 300.25,
            'min' => 100.10,
            'count' => 4
        ]);
    
        $response = $this->deleteJson('/api/transactions');
        Log::info('Resposta da limpeza de transações', ['response' => $response->getContent()]);
    }
    
}
