<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class SyncDataToFileTest extends TestCase
{
    public function test_sync_data_to_file(): void
    {
        // Configura o mock para o Storage
        Storage::fake('local');

        // Cria uma instância do StatisticsHelper
        $helper = new StatisticsHelper();

        // Adiciona alguns dados de teste
        $helper->addTransaction(100.75, time());
        $helper->addTransaction(200.10, time());

        // Chama o método syncDataToFile
        $helper->syncDataToFile();

        // Verifica se o arquivo foi criado/atualizado
        $filePath = 'transactions.json';
        Storage::disk('local')->assertExists($filePath);

        // Lê o arquivo e verifica os dados
        $data = json_decode(Storage::disk('local')->get($filePath), true);

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
}
