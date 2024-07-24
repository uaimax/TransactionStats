<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Helpers\SemaphoreHelper;
use Tests\TestCase;

class SemaphoreTest extends TestCase
{
    /**
     * Testando o semaforo
     */
    public function testSemaphore(): void
    {
        $key = 'A'; // Apenas uma chave de teste
        $result = [];

        // Callback para modificar o resultado
        $callback = function() use ($result) {
            $result[] = 'executed';
            return true;
        };

        // Abrir semaforo
        SemaphoreHelper::Semaphore($key, $callback);

        
    }
}
