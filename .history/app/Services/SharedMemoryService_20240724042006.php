<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SharedMemoryService
{
    protected $shm_id;
    protected $size;
    //const SHM_KEY = 123456;

    // Setar o construtor
    public function __construct($key = null, $size = 10000)
    {
        // Gerar uma chave de IPC baseada no arquivo atual
        $key = $key ?: ftok(__FILE__, 't');
        $this->size = $size; // Definindo o tamanho do segmento


        // Abre ou cria um segmento de memória compartilhada com a chave
        $this->shm_id = shmop_open($key, "c", 0644, $size);

        // Verifica se o segmento de memória compartilhada foi criado
        if(!$this->shm_id){
            throw new \Exception("Não foi possível criar o segmento de memória compartilhada"); // Lança uma exceção se a criação falhar
        }

        if(shmop_read($this->shm_id, 0, $size) === str_repeat("\0", $size)) {
            shmop_write($this->shm_id, serialize([]), 0);
        }
    }

    // Metódo para escrever dados na memoria
    public function write($transaction)
    {
        $existingData = $this->read();
        $existingData[] = $data;
        $serializedData = serialize($existingData);
        if (strlen($serializedData) > $this->size) {
            throw new \Exception("Os dados excedem o tamanho do segmento de memória.");
        }
        shmop_write($this->shm_id, $serializedData, $offset);
    }

    // Metódo para ler dados da memória compartilhada
    public function read($offset = 0, $size = null)
    {
        $size = $size ?: $this->size; // Define o tamanho de leitura como o tamanho do segmento, se não especificado
        // Lê os dados da memória compartilhada a partir do offset e tamanho especificados
        $data = shmop_read($this->shm_id, $offset, $size);
        if ($data === false || trim($data) === '') {
            return []; // Retornar um array vazio se a memória compartilhada estiver vazia ou se a leitura falhar
        }
        Log::info('Dados brutos lidos da memória', ['data' => $data]);
        return unserialize($data); // Deseraliza os dados e os retorna ao formato original
    }

    // Metódo para deletar o segemnto de memória compartilhada
    public function delete($transaction = null) 
    {
        if ($transaction === null) {
            // Excluir toda a memória compartilhada
            shmop_delete($this->shm_id);
            $this->shm_id = shmop_open(ftok(__FILE__, 't'), "c", 0644, $this->size);
            return;
        }
        $data = $this->read();
        $index = array_search($transaction, $data);
        if($index !== false) {
            unset($data[$index]);
            $serializedData = serialize($data);
            shmop_delete($this->shm_id);

        }
    }

}