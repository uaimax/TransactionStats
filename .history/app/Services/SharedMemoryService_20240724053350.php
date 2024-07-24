<?php

namespace App\Services;

class SharedMemoryService
{
    protected $shm_id;
    protected $size = 1024;

    // Setar o construtor
    public function __construct()
    {
        $key = ftok(__FILE__, 't');
        // Gerar uma chave de IPC baseada no arquivo atual

        // Abre ou cria um segmento de memória compartilhada com a chave
        $this->shm_id = shmop_open($key, "c", 0644, $this->size);


        // Verifica se o segmento de memória compartilhada foi criado
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
        $data = serialize($data); // Serializar os dados para transforma-los em string
        // Verifica se os dados serializados cabem no segmento
        if (strlen($data) > $this->size) {
            throw new \Exception("Os dados excedem o tamanho do segemento de memória");
        }
        // Escrevo os dados serializados na memória compartilhada a partir do offset especificado
        shmop_write($this->shm_id, $data, $offset);
    }

    // Metódo para ler dados da memória compartilhada
    public function read($offset = 0, $size = null)
    {
        $size = $size ?: $this->size; // Define o tamanho de leitura como o tamanho do segmento, se não especificado
        // Lê os dados da memória compartilhada a partir do offset e tamanho especificados
        $data = shmop_read($this->shm_id, $offset, $size);
        if ($data === str_repeat("\0", $size)) {
            return []; // Retornar um array vazio se a memória compartilhada estiver vazia
        }
        return unserialize($data); // Deseraliza os dados e os retorna ao formato original
    }

    // Metódo para deletar o segemnto de memória compartilhada
    public function delete() 
    {
        shmop_delete($this->shm_id);
    }

}