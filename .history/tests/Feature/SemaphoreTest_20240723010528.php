<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Helpers\SemaphoreHelper;
use Illuminate\Support\Facades\Log;
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
            Log::info("Callback iniciado");
            $result[] = 'executed';
            return true;
        };

        // Abrir semaforo
        try {
            // Executar o semáforo
            SemaphoreHelper::Semaphore($key, $callback);
        } catch (\Exception $e) {
            Log::error("Exception occurred: " . $e->getMessage());
            $this->fail("Exception occurred: " . $e->getMessage());
        }

        // Verificar se o callback foi executado
        $this->assertCount(1, $result);
        $this->assertEquals('executed', $result[0]);
    }
}
