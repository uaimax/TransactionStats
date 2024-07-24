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
    protected $numRequests = 1000;
}