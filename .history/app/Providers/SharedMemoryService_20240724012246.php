<?php

namespace App\Services;

class SharedMemoryService
{
    protected $shm_id;
    protected $size;

    // Setar o construtor
    public function __construct($size = 1024)
    {
        
    }
}