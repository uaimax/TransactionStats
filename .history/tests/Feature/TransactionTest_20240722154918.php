<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class TransactionTest extends TestCase
{
    public function test_store_transaction_success(): void
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100.50,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);

        $response->assertStatus(201);
    }

    public function test_store_transaction_older_than_60_seconds(): void
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100.50,
            'timestamp' => Carbon::now()->subSeconds(61)->toIso8601String(),
        ]);

        $response->assertStatus(204);
    }

    public function test_store_transaction_future_timestamp(): void
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100.50,
            'timestamp' => Carbon::now()->addSeconds(61)->toIso8601String(),
        ]);

        $response->assertStatus(422);
    }

    public function test_store_transaction_invalid_data(): void
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 'invalid',
            'timestamp' => 'invalid',
        ]);

        $response->assertStatus(400);
    }
}
