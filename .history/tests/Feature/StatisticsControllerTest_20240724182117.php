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
        // Log 1: Adicionando uma transação antiga via API
        $response = $this->postJson('/api/transactions', ['amount' => 100.75, 'timestamp' => Carbon::now()->subSeconds(61)->format('Y-m-d\TH:i:s.v\Z')]);
        Log::info('Log 1: Response status', ['status' => $response->getStatusCode()]);

        // Log 2: Adicionando transações válidas via API
        $response1 = $this->postJson('/api/transactions', ['amount' => 200.10, 'timestamp' => Carbon::now()->subSeconds(10)->format('Y-m-d\TH:i:s.v\Z')]);

        $response2 = $this->postJson('/api/transactions', ['amount' => 300.10, 'timestamp' => Carbon::now()->subSeconds(30)->format('Y-m-d\TH:i:s.v\Z')]);
        Log::info('Log 3: Response 2 status', ['status' => $response2->getStatusCode()]);

        // Log 4: Verificando as estatísticas via API
        $response = $this->getJson('/api/statistics');
        Log::info('Log 4: Estatísticas atuais', ['data' => $response->json()]);

        // Verifica se a transação antiga não é considerada
        $response->assertStatus(201)
                 ->assertJson([
                    'sum' => 500.20,      // Soma dos valores das transações válidas
                    'avg' => 250.10,      // Média dos valores das transações válidas
                    'max' => 300.10,      // Maior valor entre as transações válidas
                    'min' => 200.10,      // Menor valor entre as transações válidas
                    'count' => 2          // Número de transações válidas
                 ]);

        // Log 5: Espera 61 segundos para garantir que as transações expiram
        Log::info('Log 5: Esperando 61 segundos');
        sleep(61);

        // Log 6: Verificando novamente as estatísticas via API
        Log::info('Log 6: Verificando novamente as estatísticas via API');
        $response = $this->getJson('/api/statistics');
        Log::info('Log 6: Estatísticas após 61 segundos', ['data' => $response->json()]);

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
