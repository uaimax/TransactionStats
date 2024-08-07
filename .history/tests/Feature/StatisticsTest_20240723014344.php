<?php

namespace Tests\Feature;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StatisticsTest extends TestCase
{

    protected function setUp(): void
    {
        
    }
    // Teste para verificar se estão retornando corretamente
    public function test_get_statistics(): void
    {
        // Teste com transações válidas
        StatisticsHelper::addTransaction(100.10, Carbon::now()->subSeconds(10));
        StatisticsHelper::addTransaction(100.75, Carbon::now()->subSeconds(20));
        StatisticsHelper::addTransaction(100.20, Carbon::now()->subSeconds(30));
        StatisticsHelper::addTransaction(300.25, Carbon::now()->subSeconds(40));
        
        $response = $this->getJson(('/api/statistics'));

        $response->assertStatus(200)
        ->assertJson([
            'sum' => 601.50,
            'avg' => 200.50,
            'max' => 300.25,
            'min' => 100.50,
            'count' => 3
        ]);
    }

    public function test_get_statistics_after_cleanup(): void{
        // Adicionar algumas que sejam mais antigas que 60 segundos
        StatisticsHelper::addTransaction(100.75, Carbon::now()->subSeconds(61));
        StatisticsHelper::addTransaction(100.20, Carbon::now()->subSeconds(70));
        StatisticsHelper::addTransaction(300.25, Carbon::now()->subSeconds(95));
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
