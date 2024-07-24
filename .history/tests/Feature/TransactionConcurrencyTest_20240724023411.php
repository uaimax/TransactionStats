<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class StatisticsControllerTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteJson('/api/transactions');
    }
    // Teste para verificar se estão retornando corretamente
    public function test_get_statistics(): void
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
                    'sum' => 500.20,      // Soma dos valores das transações válidas
                    'avg' => 250.10,      // Média dos valores das transações válidas
                    'max' => 300.10,      // Maior valor entre as transações válidas
                    'min' => 200.10,      // Menor valor entre as transações válidas
                    'count' => 2          // Número de transações válidas
                 ]);
        // Espera 61 segundos para garantir que as transações expiram
        sleep(61);
        // Verificando novamente as estatísticas via API
        $response = $this->getJson('/api/statistics');
        $response->assertStatus(200)
                ->assertJson([
                    'sum' => 0.0,
                    'avg' => 0.0,
                    'max' => 0.0,
                    'min' => 0.0,
                    'count' => 0
                ]);
    }
}
