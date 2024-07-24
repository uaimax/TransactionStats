<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemaphoreTest extends TestCase
{
    /**
     * Testando o semaforo
     */
    public function testSemaphore(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
