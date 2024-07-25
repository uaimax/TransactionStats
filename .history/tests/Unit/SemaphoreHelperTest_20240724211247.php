<?php

namespace Tests\Unit;

use App\Helpers\SemaphoreHelper;
use Tests\TestCase;

class SemaphoreHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Configure the Laravel container
        $app = new Container();
        $app->singleton('app', Container::class);
        $app->singleton('files', function () {
            return new \Illuminate\Filesystem\Filesystem;
        });
        $app->singleton('log', function () use ($app) {
            return new \Illuminate\Log\LogManager($app);
        });
        $app->singleton('config', function () {
            return [
                'logging.channels' => [
                    'stack' => [
                        'driver' => 'stack',
                        'channels' => ['single'],
                    ],
                    'single' => [
                        'driver' => 'single',
                        'path' => storage_path('logs/laravel.log'),
                    ],
                ],
            ];
        });

        Facade::setFacadeApplication($app);
        (new LogServiceProvider($app))->register();
        (new FilesystemServiceProvider($app))->register();

        Log::swap($app->make('log'));

        // Define the storage path manually
        $storagePath = realpath(__DIR__ . '/../../storage');
        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }
        $app->instance('path.storage', $storagePath);
    }
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
            throw new \Exception("Exception occurred: " . $e->getMessage());
        }

        // Verificar se o callback foi executado
        $this->assertCount(1, $result);
        $this->assertEquals('executed', $result[0]);
    }
}
