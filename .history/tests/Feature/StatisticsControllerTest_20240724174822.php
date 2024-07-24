<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StatisticsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteJson('/api/transactions');
    }

    // Teste para verificar se as transações são adicionadas e limpas corretamente
    public function test_get_statistics_after_cleanup(): void
    {
        // Adicionando uma transação antiga via API
        $response = $this->postJson('/api/transactions', ['amount' => 100.75, 'timestamp' => Carbon::now()->subSeconds(61)->format('Y-m-d\TH:i:s.v\Z')]);
        $response->assertStatus(204); // Transação antiga, deve retornar 204

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
