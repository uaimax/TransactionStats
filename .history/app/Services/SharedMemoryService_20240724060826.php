<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SharedMemoryService
{
    protected $shm_id;
    protected $size = 4096;

    // Setar o construtor
    public function __construct()
    {
        $key = ftok(__FILE__, 't');
        $this->shm_id = shmop_open($key, "c", 0644, $this->size);

        if (!$this->shm_id) {
            throw new \Exception("Não foi possível criar o segmento de memória compartilhada");
        }

        if (shmop_read($this->shm_id, 0, $this->size) === str_repeat("\0", $this->size)) {
            shmop_write($this->shm_id, serialize([]), 0);
        }
    }

    // Metódo para escrever dados na memoria
    public function write($data, $offset = 0)
    {
        $data = serialize($data); 
        Log::info('Dados serializados', ['length' => strlen($data), 'data' => $data]);
        
        if (strlen($data) > $this->size) {
            throw new \Exception("Os dados excedem o tamanho do segmento de memória");
        }
        shmop_write($this->shm_id, $data, $offset);
    }

    // Metódo para ler dados da memória compartilhada
    public function read($offset = 0, $size = null)
    {
        $size = $size ?: $this->size;
        $data = shmop_read($this->shm_id, $offset, $size);
        
        if ($data === str_repeat("\0", $size)) {
            return [];
        }
    
    
        $unserializedData = @unserialize($data);
    
        if ($unserializedData === false && $data !== 'b:0;') {
            Log::error('Erro ao deserializar os dados da memória compartilhada', ['data' => $data]);
            throw new \Exception("Erro ao deserializar os dados da memória compartilhada.");
        }
    
        return $unserializedData;
    }

    // Metódo para deletar o segemnto de memória compartilhada
    public function delete() 
    {
        shmop_delete($this->shm_id);
    }

}