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
    protected $numRequestsList = [3, 9, 1000];

    protected function setUp(): void
    {
        parent::setUp();

        // Usar uma chave de memória personalizada para os testes
        $memoryKey = ftok(__FILE__, 't');
        $this->statisticsHelper = \App\Helpers\StatisticsHelper::getInstance($memoryKey);
        $this->statisticsHelper->reset(); // Limpar as transações antes de cada teste

        // Inicializar o cliente HTTP
        $this->client = new Client(['base_uri' => 'http://transactionstats.test']);
    }


    public function test_concurrent_requests()
    {

        foreach ($this->numRequestsList as $numRequests){
            $this->statisticsHelper->reset();

        };

        // Dados da transação a serem enviados
        $transactions = array_fill(0, $numRequests, [
            'amount' => 100.00,
            'timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z')
        ]);


        // Dados da transação a serem enviados
        $transactions = array_fill(0, $numRequests, [
            'amount' => 100.00,
            'timestamp' => Carbon::now()->format('Y-m-d\TH:i:s.v\Z')
        ]);

        // Cria as promessas para enviar requisições
        $promises = [];
        foreach ($transactions as $transaction) {
            $promises[] = $this->client->postAsync('/api/transactions', [
                'json' => $transaction
            ]);
        }

        // Medir o tempo de execução das requisições
        $start = microtime(true);
        $responses = Utils::settle($promises)->wait();
        $end = microtime(true);
        $duration = $end - $start;

        Log::info("POST /transactions executou em {$duration} segundos para {$numRequests} solicitações.");

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
        $response = $this->client->get('/api/statistics');
        $statistics = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals($numRequests * 100.00, $statistics['sum']); // numRequests * 100.00
        $this->assertEquals(100.00, $statistics['avg']);
        $this->assertEquals(100.00, $statistics['max']);
        $this->assertEquals(100.00, $statistics['min']);
        $this->assertEquals($numRequests, $statistics['count']);

        // Limpar as transações após cada conjunto de testes
        $this->client->delete('/api/transactions');
    }
}
