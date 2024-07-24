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

        // Verifica se o segmento de memória compartilhada foi criado
        if(!$this->shm_id){
            throw new \Exception("Não foi possível criar o segmento de memória compartilhada"); // Lança uma exceção se a criação falhar
        }
    }

    // Metódo para escrever dados na memoria
    public function write($data, $offset = 0)
    {
        $data = serialize($data); // Serializar os dados para transforma-los em string
        // Verifica se os dados serializados cabem no segmento
        if (strlen($data) > $this->size) {
            throw new \Exception("Os dados excedem o tamanho do segemento de memória");
        }
        // Escrevo os dados serializados na memória compartilhada a partir do offset especificado
        shmop_write($this->shm_id, $data, $offset);
    }

    // Metódo para ler dados da memória compartilhada
    
}