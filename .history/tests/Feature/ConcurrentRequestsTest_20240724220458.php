<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Tests\TestCase;
use Carbon\Carbon;

class ConcurrentRequestsTest extends TestCase
{
    public function testConcurrentRequests()
    {
        $client = new Client(['base_uri' => 'http://transactionstats.test']);

        $transactions = [
            ['amount' => 10.5, 'timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z')],
            ['amount' => 20.75, 'timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z')],
            ['amount' => 30.25, 'timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z')]
        ];

        $promises = [];

        // Enviar requisições concorrentes
        foreach ($transactions as $transaction) {
            $promises[] = $client->postAsync('/api/transactions', [
                'json' => $transaction
            ]);
        }

        // Esperar todas as promessas serem resolvidas
        $responses = Utils::settle($promises)->wait();

        foreach ($responses as $response) {
            if (isset($response['value'])) {
                $this->assertEquals(201, $response['value']->getStatusCode());
            } else {
                // Handle the case where the promise was rejected
                $this->fail('Request failed: ' . $response['reason']);
            }
        }

        // Esperar pelo próximo minuto para garantir que as estatísticas sejam calculadas corretamente
        $nextMinute = Carbon::now()->addMinute();
        $sleepTime = $nextMinute->diffInSeconds(Carbon::now()) + 1;
        sleep($sleepTime);

        // Verificar estatísticas
        $response = $client->get('/api/statistics');
        $this->assertEquals(200, $response->getStatusCode());

        $statistics = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('61.50', $statistics['sum']);
        $this->assertEquals('20.50', $statistics['avg']);
        $this->assertEquals('30.25', $statistics['max']);
        $this->assertEquals('10.50', $statistics['min']);
        $this->assertEquals(3, $statistics['count']);
    }
}
