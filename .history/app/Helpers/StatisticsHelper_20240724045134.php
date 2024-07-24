<?php

namespace App\Helpers;

use App\Services\SharedMemoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SplDoublyLinkedList;
use SplQueue;

class StatisticsHelper 
{
    private static $instance = null;

    private $memoryService;
    private $memoryKey;


    // Guardar todas as transações aqui
    private $transactions;

    // Guardar sobre a soma das transações
    private $sum = 0.0;

    // Guardar a contagem de transações
    private $count = 0;

    // Guardamos o menor valor de transação
    private $min = null;

    // Guardamos o maior valor de transação
    private $max = null;

    private function __construct()
    {
        $this->transactions = new SplDoublyLinkedList($memoryKey = null);
        $this->transactions->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);

        $this->memoryService = new SharedMemoryService($memoryKey);  // Iniciando o serviço de memória compartilhada
        $this->loadStatisticsFromMemory();
    }
    protected function loadTransactionsFromMemory()
    {
        $storedData = $this->memoryService->read();
        Log::info('Dados lidos da memória compartilhada', ['storedData' => $storedData]);
    
        if ($storedData) {
            $this->transactions = new SplQueue();
            foreach ($storedData['transactions'] as $transaction) {
                $this->transactions->enqueue($transaction);
            }
            $this->sum = $storedData['sum'];
            $this->count = $storedData['count'];
            $this->min = $storedData['min'];
            $this->max = $storedData['max'];
        }
    }
    
    public static function getInstance($memoryKey = null)
    {
        if (self::$instance === null) {
            Log::info('Criando nova instância de StatisticsHelper');
            self::$instance = new self($memoryKey);
        }
        return self::$instance;
    }

    public function getTransactionsArray() {
        $transactionsArray = [];
        foreach ($this->transactions as $transaction) {
            $transactionsArray[] = $transaction;
        }
        return $transactionsArray;
    }
    

    // Função para adicionar uma nova transação
    public function addTransaction($amount, $timestamp)
    {
        $timestamp = Carbon::parse($timestamp);
        $transaction = ['amount' => $amount, 'timestamp' => $timestamp];
        
        if (isset($transaction['amount']) && isset($transaction['timestamp'])) {
            $this->transactions->push($transaction);
            $this->memoryService->write($transaction);

            $this->sum += $amount;
            $this->count++;
            $this->min = ($this->min === null || $amount < $this->min) ? $amount : $this->min;
            $this->max = ($this->max === null || $amount > $this->max) ? $amount : $this->max;

            Log::info('Transação adicionada', [
                'amount' => $amount,
                'timestamp' => $timestamp,
                'sum' => $this->sum,
                'count' => $this->count,
                'min' => $this->min,
                'max' => $this->max
            ]);
        } else {
            Log::warning('Tentativa de adicionar transação inválida', ['transaction' => $transaction]);
        }
        return true;
    }

    public function getStatistics() {

        // Obtém a data e hora atuais
        $now = Carbon::now();
        $expireTime = $now->subSeconds(60); // Tempo de 60 segundos atrás
        
        // Limpar transações antigas, assim não retorna elas. 
        $this->cleanUpOldTransactions($expireTime);
        
        $avg = ($this->count > 0) ? round($this->sum / $this->count, 2) : 0.00;

        // Log para exibir todas as transações atuais
        Log::info('Transações antes do cálculo de estatísticas', ['transactions' => $this->getTransactionsArray()]);

        Log::info('Calculando estatísticas', [
            'sum' => $this->sum,
            'avg' => $avg,
            'max' => $this->max,
            'min' => $this->min,
            'count' => $this->count
        ]);
        // Retornarmos as estatísticas calculadas;
        // Um dos requisitos é o HALF_ROUND_UP, no entanto o PHP já utiliza arredondamento para cima com a função number_format.
        return [
            'sum' => number_format($this->sum, 2, '.', ''),
            'avg' => number_format($avg, 2, '.', ''),
            'max' => number_format($this->max, 2, '.', ''),
            'min' => number_format($this->min, 2, '.', ''),
            'count' => $this->count
        ];
    }

    public function cleanUpOldTransactions($expireTime)
    {
    
        while (!$this->transactions->isEmpty()) {
            $transaction = $this->transactions->bottom();
            $transactionTimestamp = Carbon::parse($transaction['timestamp']);
    
            if ($transactionTimestamp->lt($expireTime)) {
                $this->transactions->shift();
                $this->sum -= $transaction['amount'];
                $this->count--;
            } else {
                break;
            }
        }
        if ($this->count > 0) {
            $this->min = min(array_column(iterator_to_array($this->transactions), 'amount'));
            $this->max = max(array_column(iterator_to_array($this->transactions), 'amount'));
        } else {
            $this->min = null;
            $this->max = null;
        }
    
        $this->memoryService->write(iterator_to_array($this->transactions));
        Log::info('Transações após a limpeza', ['transactions' => iterator_to_array($this->transactions)]);
    }
    

    public function reset()
    {
        
        // Limpa a memória compartilhada
        $this->memoryService->delete();

        // Redefine o serviço de memória compartilhada
        $this->memoryService = new SharedMemoryService($this->memoryKey);

        // Redefine a lista de transações e estatísticas
        $this->transactions = new SplDoublyLinkedList();
        $this->transactions->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
        $this->sum = 0.0;
        $this->count = 0;
        $this->min = null;
        $this->max = null;

        // Persistir o estado limpo na memória compartilhada
        $this->memoryService->write([]);
        Log::info('Resetando lista de transações e estatísticas');
    }

    // Metódos de acesso público para permitir testarmos
    public function getCount() {
        return $this->count;
    }

    public function getSum() {
        return $this->sum;
    }

    public function getMin() {
        return $this->min;
    }

    public function getMax() {
        return $this->max;
    }


}