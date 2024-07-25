<?php

namespace Tests\Feature;
use Tests\TestCase;
use Carbon\Carbon;

class TransactionEndpointTest extends TestCase
{
    /**
     * Testando o endpoint POST /api/transactions com uma transação válida.
     */
    public function testPostTransactionValid()
    {
        $payload = [
            'amount' => 12.3343,
            'timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z')
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(201);
    }

    /**
     * Testando o endpoint POST /api/transactions com uma transação antiga.
     */
    public function testPostTransactionOld()
    {
        $payload = [
            'amount' => 12.3343,
            'timestamp' => Carbon::now()->subMinutes(2)->format('Y-m-d\TH:i:s.v\Z')
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(204);
    }

    /**
     * Testando o endpoint POST /api/transactions com um JSON inválido.
     */
    public function testPostTransactionInvalidJson()
    {
        $payload = [
            'amount' => 12.3343,
            // Remover intencionalmente o timestamp para simular um JSON inválido
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(422);
    }

    /**
     * Testando o endpoint POST /api/transactions com um timestamp no futuro.
     */
    public function testPostTransactionFutureTimestamp()
    {
        $payload = [
            'amount' => 12.3343,
            'timestamp' => Carbon::now()->addMinutes(2)->format('Y-m-d\TH:i:s.v\Z')
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(422);
    }

    /**
     * Testando o endpoint POST /api/transactions com campos inválidos.
     */
    public function testPostTransactionInvalidFields()
    {
        $payload = [
            'amount' => 'invalid_amount',
            'timestamp' => 'invalid_timestamp'
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(422);
    }

    /**
     * Testando o endpoint GET /statistics sem transações.
     */
    public function testGetStatisticsNoTransactions()
    {
        $response = $this->getJson('/api/statistics');

        $response->assertStatus(201)
                 ->assertJson([]);
    }

    /**
     * Testando o endpoint GET /statistics com várias transações.
     */
    public function testGetStatisticsWithTransactions()
    {
        $transactions = [
            ['amount' => 10.5, 'timestamp' => Carbon::now()->subMinutes(1)->format('Y-m-d\TH:i:s.v\Z')],
            ['amount' => 20.75, 'timestamp' => Carbon::now()->subMinutes(1)->format('Y-m-d\TH:i:s.v\Z')],
            ['amount' => 30.25, 'timestamp' => Carbon::now()->subMinutes(1)->format('Y-m-d\TH:i:s.v\Z')],
        ];
        var_dump()
        foreach ($transactions as $transaction) {
            $this->postJson('/api/transactions', $transaction);
        }

        $response = $this->getJson('/api/statistics');

        $response->assertStatus(201)
                 ->assertJson([
                     'sum' => '61.50',
                     'avg' => '20.50',
                     'max' => '30.25',
                     'min' => '10.50',
                     'count' => 3,
                 ]);
    }

    /**
     * Testando o endpoint DELETE /api/transactions.
     */
    public function testDeleteTransactions()
    {
        $transactions = [
            ['amount' => 10.5, 'timestamp' => Carbon::now()->toISOString()],
            ['amount' => 20.75, 'timestamp' => Carbon::now()->toISOString()],
            ['amount' => 30.25, 'timestamp' => Carbon::now()->toISOString()],
        ];

        foreach ($transactions as $transaction) {
            $this->postJson('/api/transactions', $transaction);
        }

        $response = $this->deleteJson('/api/transactions');

        $response->assertStatus(204);

        // Verifica se todas as transações foram excluídas
        $response = $this->getJson('/api/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'sum' => '0.00',
                     'avg' => '0.00',
                     'max' => '0.00',
                     'min' => '0.00',
                     'count' => 0,
                 ]);
    }
}
