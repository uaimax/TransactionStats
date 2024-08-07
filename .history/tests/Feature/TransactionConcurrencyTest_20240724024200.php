<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\Utils;
use Tests\TestCase;

class TransactionConcurrencyTest extends TestCase
{

    public function test_concurrent_requests()
    {
        $client = new Client(['base_uri' => 'http://transactionstats.test/']);

        // Requisições concorrentes
        $numRequests = 10;

        // Dados da transação a serem enviados
        $transactions = array_fill(0, $numRequests, [
            'amount' => 100,00,
            'timestamp' => now()->toISOString()
        ]);

        // Cria as promessas para enviar requisições
        $promises = [];
        foreach ($transactions as $transaction) {
            $promises[] = $client->postAsync('/api/transactions', [
                'json' => $transaction
            ]);
        }

        // Espera que todas as promessas sejam resolvidas
        $responses = Utils::settle($promises)->wait();

        // Verifica se todas foram bem sucedidas
        foreach ($responses as $response) {
            $this->assertEquals('fulfilled', $response['state']);
            $this->assertEquals(200, $response['value']->getStatusCode());
        }

        // Verifica a consistência dos dados
        $response = $client->get('/api/statistics')

    }
}
