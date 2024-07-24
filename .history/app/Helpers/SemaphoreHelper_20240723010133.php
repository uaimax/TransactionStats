<?php

namespace App\Helpers;

class SemaphoreHelper
{
    public static function Semaphore($key, $callback){
        
        $lockFile = sys_get_temp_dir() . '/semaphore_' . md5($key) . '.lock';
        $fp = fopen($lockFile, 'c');

        if(!$fp) {
            Log::error("Não conseguiu criar o arquivo de lock");
            throw new \Exception("Não encontrou o semaforo");
        }

        // Adquirir semaforo
        if (!flock($fp, LOCK_EX)) {
            throw new \Exception("Não conseguiu adquirir o semaforo");
        }

        try {
            return $callback();
        } finally {
            // Liberar o semáforo
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}