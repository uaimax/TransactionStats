<?php

namespace App\Helpers;

class SemaphoreHelper
{
    public static function Semaphore($key, $callback){
        $sem = sem_get(ftok(__FILE__, $key), 1, 0666, 1);
    }
}