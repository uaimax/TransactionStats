<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    // Teste para verificar se a transação é armazenada com sucesso
    public function test_store_transaction_success(): void //Adicionando void para deixar explícito que o metódo não retornará nenhum valor, apenas por boa prática.
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100.50,
            'timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z'),
        ]);

        $response->assertStatus(201);
    }

    // Teste para verificar se a transação mais antiga que 60 segundos retorna 204.
    public function test_store_transaction_older(): void 
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100.50,
            'timestamp' => Carbon::now()->subSeconds(61)->format('Y-m-d\TH:i:s.v\Z'),
        ]);

        $response->assertStatus(204);
    }

    // Teste para verificar se o timestamp no futuro retorna 422
    public function test_store_transaction_future_timestamp(): void
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100.50,
            'timestamp' => Carbon::now()->addSeconds(61)->format('Y-m-d\TH:i:s.v\Z'),
        ]);

        $response->assertStatus(422);
    }

    // Efetua verificações com dados inválidos de uma forma geral.

    public function test_store_transaction_invalid_data(): void 
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100.50,
            'timestamp' => a,
            
        ]);

        $response->assertStatus(400);
    }
}
