<?php

namespace App\Services;

class SharedMemoryService
{
    protected $shm_id;
    protected $size;

    // Setar o construtor
    public function __construct($size = 1024)
    {
        // Gerar uma chave de IPC baseada no arquivo atual
        $key = ftok(__FILE__, 't');
        $this->size = $size; // Definindo o tamanho do segmento

        // Abre ou cria um segmento de memória compartilhada com a chave
        $this->shm_id = shmop_open($key, "c", 0644, $size);
        
    }
}