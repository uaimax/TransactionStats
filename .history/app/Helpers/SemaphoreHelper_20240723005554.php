<?php

namespace App\Helpers;

class SemaphoreHelper
{
    public static function Semaphore($key, $callback){
        
        $lockfile = sys_get_temp_dir() . '/semaphore_' . md5($key) . '.lock';
        $fp = fopen($lockFile, 'c');

        if(!$fp) {
            throw new \Exception("Não encontrou o semaforo");
        }

        // Adquirir semaforo
        if (!flock) {
            throw new \Exception("Não conseguiu adquirir o semaforo");
        }

        try {
            return $callback();
        } finally {
            // Liberar o semáforo
            sem_release($fp);
        }
    }
}