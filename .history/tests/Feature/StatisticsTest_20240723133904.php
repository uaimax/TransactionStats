<?php

namespace Tests\Feature;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StatisticsTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Log::info('Executando setUp antes do teste.');

        $this->deleteJson('/api/transactions');
    }
    // Teste para verificar se estão retornando corretamente
    public function test_get_statistics(): void
    {
        // Adicionando estatísticas válidas

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

    // Teste para verificar se as transações antigas são limpas corretamente
    public function test_get_statistics_after_cleanup(): void
    {
        // Adicionando uma transação antiga via API e capturando a exceção
        try {
            $this->postJson('/api/transactions', ['amount' => 100.75, 'timestamp' => Carbon::now()->subSeconds(61)->format('Y-m-d\TH:i:s.v\Z')]);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Transação tem mais de 60 segundos', $e->getMessage());
        }

        $response = $this->deleteJson('/api/transactions');
        Log::info('Resposta da limpeza de transações', ['response' => $response->getContent()]);

        // Adicionando transações válidas via API
        $this->postJson('/api/transactions', ['amount' => 200.10, 'timestamp' => Carbon::now()->subSeconds(10)->format('Y-m-d\TH:i:s.v\Z')]);
        $this->postJson('/api/transactions', ['amount' => 300.10, 'timestamp' => Carbon::now()->subSeconds(30)->format('Y-m-d\TH:i:s.v\Z')]);

        // Verificando as estatísticas via API
        $response = $this->getJson('/api/statistics');

        // Verifica se a transação antiga não é considerada
        $response->assertStatus(200)
                 ->assertJson([
                     'sum' => 500.20,
                     'avg' => 250.50,
                     'max' => 300.25,
                     'min' => 200.75,
                     'count' => 2
                 ]);
    }
}
