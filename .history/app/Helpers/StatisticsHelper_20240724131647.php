<?php

namespace App\Helpers;

use App\Services\SharedMemoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SplDoublyLinkedList;

class StatisticsHelper 
{

    // Vou efetuar uma abordagem com arquivo.
    private $filePath;
    private $timeWindow = 60; // Janela de 60 segundos

    private function __construct($filePath = 'transactions.json')
    {
        // Guardo caminho do arquivo em uma váriavel
        $this->filePath = $filePath;

        // Se o arquivo ainda não existir, criamos ele com dados iniciais.
        if (!file_exists($this->filePath)) {
            // Salvamos um arquivo JSON com uma lista de transações vazias e outras estatísticas.
            file_put_contents($this->filePath, json_encode([
                'transactions' => [],
                'sum' => 0.0,
                'count' => 0,
                'max' => PHP_FLOAT_MIN,
                'min' => PHP_FLOAT_MAX
            ]));
        }


    }
    protected function loadTransactionsFromMemory()
    {
        try {
            $storedTransactions = $this->memoryService->read();
        } catch (\Exception $e) {
            Log::error('Erro ao carregar transações da memória compartilhada', ['error' => $e->getMessage()]);
            $storedTransactions = [];
        }
        Log::info('Dados lidos da memória compartilhada', ['storedTransactions' => $storedTransactions]);
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
    public static function getInstance($memoryKey = null, $size = 4096)
    {
        if (self::$instance === null) {
            Log::info('Criando nova instância de StatisticsHelper');
            self::$instance = new self($memoryKey, $size);
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
        // Converte o timestamp de string para um objeto Carbon (data/hora)
        $timestamp = Carbon::parse($timestamp);


        // Adiciona nova transação a lista
        $transaction = ['amount' => $amount, 'timestamp' => $timestamp];
        Log::info('Adicionando transação', ['transaction' => $transaction]);
    
        $this->transactions->push($transaction);

        // Atualiza a memória compartilhada com a lista de transações
        $this->memoryService->write(iterator_to_array($this->transactions));

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
        while (!$this->transactions->isEmpty()) {
            $transaction = $this->transactions->bottom();
    
            // Verifica se a transação contém a chave 'timestamp'
            if (!isset($transaction['timestamp'])) {
                Log::warning('Transação sem timestamp encontrada', ['transaction' => $transaction]);
                $this->transactions->shift(); // Remove a transação inválida
                continue; // Continua com a próxima iteração
            }
    
            $transactionTimestamp = Carbon::parse($transaction['timestamp']);
    
            if ($transactionTimestamp->lt($expireTime)) {
                $removedTransaction = $this->transactions->shift();
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
    
        // Escrever transações atualizadas na memória compartilhada
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
        $this->memoryService->write(iterator_to_array($this->transactions));
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