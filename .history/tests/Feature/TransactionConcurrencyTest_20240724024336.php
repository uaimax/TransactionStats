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
        $client = new Client(['base_uri' => 'http://localhost:8000']);

        // Número de requisições concorrentes
        $numRequests = 10;

        // Dados da transação a serem enviados
        $transactions = array_fill(0, $numRequests, [
            'amount' => 100.00,
            'timestamp' => now()->toISOString()
        ]);

        // Cria as promessas para enviar requisições concorrentes
        $promises = [];
        foreach ($transactions as $transaction) {
            $promises[] = $client->postAsync('/api/transactions', [
                'json' => $transaction
            ]);
        }

        // Espera que todas as promessas sejam resolvidas
        $responses = Utils::settle($promises)->wait();

        // Verifica se todas as requisições foram bem-sucedidas
        foreach ($responses as $response) {
            $this->assertEquals('fulfilled', $response['state']);
            $this->assertEquals(200, $response['value']->getStatusCode());
        }

        // Verifica a consistência dos dados
        $response = $client->get('/api/statistics');
        $statistics = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(1000.00, $statistics['sum']); // 10 * 100.00
        $this->assertEquals(100.00, $statistics['avg']);
        $this->assertEquals(100.00, $statistics['max']);
        $this->assertEquals(100.00, $statistics['min']);
        $this->assertEquals(10, $statistics['count']);
    }
}
