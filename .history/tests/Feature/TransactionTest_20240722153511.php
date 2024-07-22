<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    // Teste para verificar se a transação é armazenada com sucesso
    public function test_store_transaction_success(): void //Adicionando void para deixar explícito que o metódo não retornará nenhum valor, apenas por boa prática.
    {
        $response = $this->postJson()
    }
}
