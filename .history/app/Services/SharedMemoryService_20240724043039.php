<?php

namespace App\Services;

class SharedMemoryService
{
    protected $shm_id;
    protected $size;

    public function __construct($size = 1024)
    {
        $key = ftok(__FILE__, 't');
        $this->size = $size;
        $this->shm_id = shmop_open($key, "c", 0644, $size);

        if (!$this->shm_id) {
            throw new \Exception("Não foi possível criar o segmento de memória compartilhada.");
        }
    }

    public function write($data, $offset = 0)
    {
        $existingData = $this->read();
        if (!empty($data)) {
            $existingData[] = $data;
        }
        $serializedData = serialize($existingData);
        if (strlen($serializedData) > $this->size) {
            throw new \Exception("Os dados excedem o tamanho do segmento de memória.");
        }
        shmop_write($this->shm_id, $serializedData, $offset);
    }

    public function read($offset = 0, $size = null)
    {
        $size = $size ?: $this->size;
        $data = shmop_read($this->shm_id, $offset, $size);
        if ($data === false || trim($data) === '') {
            return [];
        }
        return unserialize($data);
    }

    public function delete($transaction = null)
    {
        if ($transaction === null) {
            shmop_delete($this->shm_id);
            $this->shm_id = shmop_open(ftok(__FILE__, 't'), "c", 0644, $this->size);
            return;
        }

        $data = $this->read();
        $index = array_search($transaction, $data);
        if ($index !== false) {
            unset($data[$index]);
            $serializedData = serialize(array_values($data));
            if (strlen($serializedData) > $this->size) {
                throw new \Exception("Os dados excedem o tamanho do segmento de memória.");
            }
            shmop_write($this->shm_id, $serializedData, 0);
        }
    }
}
