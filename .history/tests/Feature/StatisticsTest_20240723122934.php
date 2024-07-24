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
        $this->deleteJson('/api/transactions');
    }
    // Teste para verificar se estão retornando corretamente
    public function test_get_statistics(): void
    {
        // Adicionando estatísticas válidas
        $this->postJson('/api/transactions', ['amount' => 100.10, 'timestamp' => Carbon::now()->subSeconds(10)->toISOString()]);
        $this->postJson('/api/transactions', ['amount' => 100.75, 'timestamp' => Carbon::now()->subSeconds(20)->toISOString()]);
        $this->postJson('/api/transactions', ['amount' => 100.20, 'timestamp' => Carbon::now()->subSeconds(30)->toISOString()]);
        $this->postJson('/api/transactions', ['amount' => 300.25, 'timestamp' => Carbon::now()->subSeconds(40)->toISOString()]);
        
        // Verificando estatísticas
        $response = $this->getJson('/api/statistics');
        error_log('Estatísticas atuais: ' . $response->getContent());
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
            $this->postJson('/api/transactions', ['amount' => 100.75, 'timestamp' => Carbon::now()->subSeconds(61)->toISOString()]);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Transação tem mais de 60 segundos', $e->getMessage());
        }

        // Adicionando transações válidas via API
        $this->postJson('/api/transactions', ['amount' => 200.75, 'timestamp' => Carbon::now()->subSeconds(50)->toISOString()]);
        $this->postJson('/api/transactions', ['amount' => 300.25, 'timestamp' => Carbon::now()->subSeconds(30)->toISOString()]);

        // Verificando as estatísticas via API
        $response = $this->getJson('/api/statistics');

        // Verifica se a transação antiga não é considerada
        $response->assertStatus(200)
                 ->assertJson([
                     'sum' => 501.00,
                     'avg' => 250.50,
                     'max' => 300.25,
                     'min' => 200.75,
                     'count' => 2
                 ]);
    }
}
