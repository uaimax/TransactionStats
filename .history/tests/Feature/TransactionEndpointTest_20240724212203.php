<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class TransactionEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testando o endpoint POST /transactions com uma transação válida.
     */
    public function testPostTransactionValid()
    {
        $payload = [
            'amount' => 12.3343,
            'timestamp' => Carbon::now()->toISOString()
        ];

        $response = $this->postJson('/transactions', $payload);

        $response->assertStatus(201);
    }

    /**
     * Testando o endpoint POST /transactions com uma transação antiga.
     */
    public function testPostTransactionOld()
    {
        $payload = [
            'amount' => 12.3343,
            'timestamp' => Carbon::now()->subMinutes(2)->toISOString()
        ];

        $response = $this->postJson('/transactions', $payload);

        $response->assertStatus(204);
    }

    /**
     * Testando o endpoint POST /transactions com um JSON inválido.
     */
    public function testPostTransactionInvalidJson()
    {
        $payload = '{invalid_json}';

        $response = $this->postJson('/transactions', $payload);

        $response->assertStatus(400);
    }

    /**
     * Testando o endpoint POST /transactions com um timestamp no futuro.
     */
    public function testPostTransactionFutureTimestamp()
    {
        $payload = [
            'amount' => 12.3343,
            'timestamp' => Carbon::now()->addMinutes(2)->toISOString()
        ];

        $response = $this->postJson('/transactions', $payload);

        $response->assertStatus(422);
    }

    /**
     * Testando o endpoint POST /transactions com campos inválidos.
     */
    public function testPostTransactionInvalidFields()
    {
        $payload = [
            'amount' => 'invalid_amount',
            'timestamp' => 'invalid_timestamp'
        ];

        $response = $this->postJson('/transactions', $payload);

        $response->assertStatus(422);
    }

    /**
     * Testando o endpoint GET /statistics sem transações.
     */
    public function testGetStatisticsNoTransactions()
    {
        $response = $this->getJson('/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'sum' => '0.00',
                     'avg' => '0.00',
                     'max' => '0.00',
                     'min' => '0.00',
                     'count' => 0,
                 ]);
    }

    /**
     * Testando o endpoint GET /statistics com várias transações.
     */
    public function testGetStatisticsWithTransactions()
    {
        $transactions = [
            ['amount' => 10.5, 'timestamp' => Carbon::now()->toISOString()],
            ['amount' => 20.75, 'timestamp' => Carbon::now()->toISOString()],
            ['amount' => 30.25, 'timestamp' => Carbon::now()->toISOString()],
        ];

        foreach ($transactions as $transaction) {
            $this->postJson('/transactions', $transaction);
        }

        $response = $this->getJson('/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'sum' => '61.50',
                     'avg' => '20.50',
                     'max' => '30.25',
                     'min' => '10.50',
                     'count' => 3,
                 ]);
    }

    /**
     * 
