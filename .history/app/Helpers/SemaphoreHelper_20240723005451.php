<?php

namespace App\Helpers;

class SemaphoreHelper
{
    public static function Semaphore($key, $callback){
        
        $lockfile = sys_get_temp_dir()

        if(!$sem) {
            throw new \Exception("Não encontrou o semaforo");
        }

        // Adquirir semaforo
        if (!sem_acquire($sem)) {
            throw new \Exception("Não conseguiu adquirir o semaforo");
        }

        try {
            return $callback();
        } finally {
            // Liberar o semáforo
            sem_release($sem);
        }
    }
}