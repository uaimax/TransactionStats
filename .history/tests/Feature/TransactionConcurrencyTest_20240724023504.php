<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class TransactionConcurrencyTest extends TestCase
{

    public function test_concurrent_requests()
    {
        $client = new Client(['base_uri' => ''])
    }
}
