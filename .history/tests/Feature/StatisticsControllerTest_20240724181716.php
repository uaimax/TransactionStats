<?php

namespace Tests\Feature;

use App\Helpers\StatisticsHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StatisticsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteJson('/api/transactions');
    }

    public function test_sync_data_to_file(): void
    {
        // Configura o mock para o Storage
        Storage::fake('local');

        // Caminho completo do arquivo
        $filePath = storage_path('app/transactions.json');
        
        // Verifica se o arquivo existe antes do teste
        if (file_exists($filePath)) {
            unlink($filePath); // Remove o arquivo se existir
        }
        $this->assertFalse(file_exists($filePath), "O arquivo não deveria existir antes do teste.");

        // Cria uma instância do StatisticsHelper
        $helper = new StatisticsHelper();

        // Adiciona alguns dados de teste
        $helper->addTransaction(100.75, time());
        $helper->addTransaction(200.10, time());

        // Chama o método syncDataToFile
        $helper->syncDataToFile();

        // Verifica se o arquivo foi criado/atualizado
        $this->assertTrue(file_exists($filePath), "O arquivo deveria ter sido criado.");

        // Lê o arquivo e verifica os dados
        $data = json_decode(file_get_contents($filePath), true);

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('transactions', $data);
        $this->assertArrayHasKey('sum', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('max', $data);
        $this->assertArrayHasKey('min', $data);
        
        // Verifica os valores
        $this->assertEquals(300.85, $data['sum']);
        $this->assertEquals(2, $data['count']);
        $this->assertEquals(200.10, $data['max']);
        $this->assertEquals(100.75, $data['min']);
    }

    // Teste para verificar se as transações são adicionadas e limpas corretamente
    public function test_get_statistics_after_cleanup(): void
    {


        // Espera 61 segundos para garantir que as transações expiram
        sleep(61);

        // Verificando novamente as estatísticas via API
        $response = $this->getJson('/api/statistics');
        $response->assertStatus(201)
                ->assertJson([
                    'sum' => 0.0,
                    'avg' => 0.0,
                    'max' => 0.0,
                    'min' => 0.0,
                    'count' => 0
                ]);
    }
}
