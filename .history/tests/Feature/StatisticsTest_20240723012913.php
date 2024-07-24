<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StatisticsTest extends TestCase
{

    
    public function test_get_statistics(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
