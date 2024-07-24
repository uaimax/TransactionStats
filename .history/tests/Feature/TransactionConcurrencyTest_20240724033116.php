<?php

namespace Tests\Feature;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;


// Optei por adicionar esse teste voltado para concorrÊncia, mas também focado em performance devido a solicitação de (O)1. 
class TransactionConcurrencyTest extends TestCase
{
    protected $statisticsHelper;

    protected $client;
    protected $numRequests = [10, 100, 1000]; // Diferentes volumes de transações

    protected function setUp(): void
    {
        parent::setUp();

        // Usar uma chave de memória personalizada para os testes
        $memoryKey = ftok(__FILE__, 't');
        $this->statisticsHelper = \App\Helpers\StatisticsHelper::getInstance($memoryKey);
    }


    public function test_concurrent_requests()
    {

        
        $client = new Client(['base_uri' => 'http://transactionstats.test/']);

        // Requisições concorrentes
        $numRequests = 3;

        // Dados da transação a serem enviados
        $transactions = array_fill(0, $numRequests, [
            'amount' => 100.00,
            'timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z')
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
            if ($response['state'] === 'rejected') {
                Log::error('Requisição rejeitada', [
                    'reason' => $response['reason']->getMessage()
                ]);
            }
            $this->assertEquals('fulfilled', $response['state']);
            if ($response['state'] === 'fulfilled') {
                $this->assertEquals(201, $response['value']->getStatusCode());
            }
        }

        // Verifica a consistência dos dados
        $response = $client->get('/api/statistics');
        $statistics = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(300.00, $statistics['sum']); // 3 * 100.00
        $this->assertEquals(100.00, $statistics['avg']);
        $this->assertEquals(100.00, $statistics['max']);
        $this->assertEquals(100.00, $statistics['min']);
        $this->assertEquals(3, $statistics['count']);
    }
}
