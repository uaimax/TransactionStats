<?php

namespace Tests\Unit;

use App\Helpers\SemaphoreHelper;
use PHPUnit\Framework\TestCase;

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
        $callback = function() use (&$result) {
            $result[] = 'executed';
            return true;
        };

        // Abrir semaforo
        try {
            // Executar o semáforo
            SemaphoreHelper::Semaphore($key, $callback);
        } catch (\Exception $e) {
            $this->fail("Exception occurred: " . $e->getMessage());
        }

        // Verificar se o callback foi executado
        $this->assertCount(1, $result);
        $this->assertEquals('executed', $result[0]);
    }
}
