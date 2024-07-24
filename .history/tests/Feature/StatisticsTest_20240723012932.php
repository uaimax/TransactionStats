<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StatisticsTest extends TestCase
{

    // Teste para verificar se estão retornando corretamente
    public function test_get_statistics(): void
    {
        // Teste com transações válidas
        

        $response->assertStatus(200);
    }
}
