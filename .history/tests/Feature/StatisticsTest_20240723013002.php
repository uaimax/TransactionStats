<?php

namespace Tests\Feature;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StatisticsTest extends TestCase
{

    // Teste para verificar se estão retornando corretamente
    public function test_get_statistics(): void
    {
        // Teste com transações válidas
        StatisticsHelper::addTransaction(100.50, Carbon::now()->subSeconds(10));

        $response->assertStatus(200);
    }
}
