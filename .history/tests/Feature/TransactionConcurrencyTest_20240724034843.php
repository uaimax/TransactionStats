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
    protected $numRequestsList = [3, 9, 12];

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
 
        };

   
    }
}
