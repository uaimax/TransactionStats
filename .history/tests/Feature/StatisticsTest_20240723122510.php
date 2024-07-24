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
        
        $response->assertStatus(200)->assertJson([
            'sum' => 601.30,
            'avg' => 150.33,
            'max' => 300.25,
            'min' => 100.10,
            'count' => 4
        ]);
    }


}
