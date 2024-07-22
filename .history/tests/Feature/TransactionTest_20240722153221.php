<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    public function test_store_transaction_success(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
