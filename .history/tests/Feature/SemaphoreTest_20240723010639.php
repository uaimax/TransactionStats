<?php
use Tests\TestCase;
use App\Helpers\SemaphoreHelper;
use Illuminate\Support\Facades\Log;

class SemaphoreTest extends TestCase
{
    public function testSemaphore()
    {
        $key = 'A'; // Use uma chave de teste
        $result = [];

        // Callback para modificar o resultado
        $callback = function() use (&$result) {
            Log::info("Callback iniciado");
            $result[] = 'executed';
            Log::info("Callback executado, resultado modificado", $result);
            return true;
        };

        try {
            Log::info("Antes de adquirir o semáforo");
            // Executar o semáforo
            SemaphoreHelper::Semaphore($key, $callback);
            Log::info("Depois de liberar o semáforo");
        } catch (\Exception $e) {
            Log::error("Exception occurred: " . $e->getMessage());
            $this->fail("Exception occurred: " . $e->getMessage());
        }

        // Verificar se o callback foi executado
        Log::info("Resultado final do callback", $result);
        $this->assertCount(1, $result);
        $this->assertEquals('executed', $result[0]);
    }
}
