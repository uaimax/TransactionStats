<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class SemaphoreHelper
{
    public static function Semaphore($key, $callback)
    {
        $lockFile = sys_get_temp_dir() . '/semaphore_' . md5($key) . '.lock';
        $fp = fopen($lockFile, 'c');
        
        if (!$fp) {
            Log::error("Não conseguiu criar o arquivo de lock");
            throw new \Exception("Não conseguiu criar o arquivo de lock");
        }

        // Bloquear o arquivo
        if (!flock($fp, LOCK_EX)) {
            Log::error("Não conseguiu adquirir o lock");
            throw new \Exception("Não conseguiu adquirir o lock");
        }

        try {
            Log::info("Semáforo adquirido");
            return $callback();
        } finally {
            // Liberar o lock
            flock($fp, LOCK_UN);
            fclose($fp);
            Log::info("Semáforo liberado");
        }
    }
}
