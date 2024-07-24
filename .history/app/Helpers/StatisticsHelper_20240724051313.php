<?php

namespace App\Helpers;

use App\Services\SharedMemoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SplDoublyLinkedList;

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

        $this->memoryService = new SharedMemoryService($memoryKey);  // Iniciando o serviço de memória compartilhara
        // Carregar transações da memória compartilhada
        $this->loadTransactionsFromMemory();
    }
    protected function loadTransactionsFromMemory()
    {
        $storedTransactions = $this->memoryService->read();
        Log::info('Dados lidos da memória compartilhada', ['storedTransactions' => $storedTransactions]);
        
        if ($storedTransactions) {
            foreach ($storedTransactions as $index => $transaction) {
                Log::info('Verificando transação', ['index' => $index, 'transaction' => $transaction]);
                if (is_array($transaction) && isset($transaction['amount']) && isset($transaction['timestamp'])) {
                    Log::info('Processando transação', ['transaction' => $transaction]);
                    $this->transactions->push($transaction);
                    $this->sum += $transaction['amount'];
                    $this->count++;
                    $this->min = ($this->min === null || $transaction['amount'] < $this->min) ? $transaction['amount'] : $this->min;
                    $this->max = ($this->max === null || $transaction['amount'] > $this->max) ? $transaction['amount'] : $this->max;
                } else {
                    Log::warning('Transação inválida encontrada', ['transaction' => $transaction]);
                }
            }
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
        $this->transactions->push($transaction);
    
        // Escreve apenas a nova transação em vez de toda a lista
        $this->memoryService->write([$transaction]);

        // Atualizamos as estatísticas
        $this->sum += $amount; // Adicionamos o valor a soma total
        $this->count++; // Incrementamos a quantidade de transações efetuadas
        $this->min = ($this->min === null || $amount < $this->min) ? $amount : $this->min; // Atualização do minimo (menor valor), somente se necessario
        $this->max = ($this->max === null || $amount > $this->max) ? $amount : $this->max; // Atualização do valor maior, somente se necessário

        Log::info('Transação adicionada', [
            'amount' => $amount,
            'timestamp' => $timestamp,
            'sum' => $this->sum,
            'count' => $this->count,
            'min' => $this->min,
            'max' => $this->max
        ]);

        // Log para exibir todas as transações atuais
        Log::info('Transações atuais', ['transactions' => $this->getTransactionsArray()]);
        // Retorna true indicando que transação foi criada
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
    
        // Remover transações antigas
        while (!$this->transactions->isEmpty()) {
            $transaction = $this->transactions->bottom();
            $transactionTimestamp = Carbon::parse($transaction['timestamp']);

            if ($transactionTimestamp->lt($expireTime)) {
                $removedTransaction = $this->transactions->shift(); // Muda para shift, pois está removendo do início
                $this->sum -= $removedTransaction['amount'];
                $this->count--;

                // Recalcular min e max somente se a transação removida afetar esses valores
                if ($this->count > 0) {
                    if ($removedTransaction['amount'] == $this->min) {
                        $this->min = min(array_column(iterator_to_array($this->transactions), 'amount'));
                    }
                    if ($removedTransaction['amount'] == $this->max) {
                        $this->max = max(array_column(iterator_to_array($this->transactions), 'amount'));
                    }
                } else {
                    $this->min = null;
                    $this->max = null;
                }
            } else {
                break;
            }
        }
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