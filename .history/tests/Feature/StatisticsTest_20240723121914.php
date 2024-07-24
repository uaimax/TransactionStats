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
        parent::setUp();
    }
    // Teste para verificar se estão retornando corretamente
    public function test_get_statistics(): void
    {
        $this->postJson
    }

    public function test_get_statistics_after_cleanup(): void{
        // Adicionar algumas que sejam mais antigas que 60 segundos
        StatisticsHelper::addTransaction(100.75, Carbon::now()->subSeconds(61));
        StatisticsHelper::addTransaction(200.75, Carbon::now()->subSeconds(50));
        StatisticsHelper::addTransaction(300.25, Carbon::now()->subSeconds(30));
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
