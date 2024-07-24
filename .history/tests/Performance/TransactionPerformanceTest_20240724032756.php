<?php
namespace Tests\Performance;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Optei por adicionar esse teste, focado em performance devido a solicitação de (O)1. 

class TransactionPerformanceTest extends TestCase{
    protected $client;
    protected $numRequests = 20; // Numero de requisições como amostragem

    protected function setUp(): void{
        parent::setUp();
        $this->client = new Client(['base_uri' => 'http://transactionstats.test']);

        public function transactions_performance()
        {
            $start = microtime(true);

            $transactions = array_fill()
        }
    }
}