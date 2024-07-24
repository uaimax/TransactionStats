<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SplDoublyLinkedList;

class StatisticsHelper 
{
    private static $instance = null;

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
        $this->transactions = new SplDoublyLinkedList();
        $this->transactions->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
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
        $this->transactions->push(['amount' => $amount, 'timestamp' => $timestamp]);

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
        Log::info('Transações atuais', ['transactions' => self::getTransactionsArray()]);
        // Retorna true indicando que transação foi criada
        return true;
    }

    public function getStatistics() {

        // Obtém a data e hora atuais
        $now = Carbon::now();
        $expireTime = $now->subSeconds(60); // Tempo de 60 segundos atrás
        
        // Limpar transações antigas, assim não retorna elas. 
        self::cleanUpOldTransactions($expireTime);
        
        $avg = (self::$count > 0) ? round(self::$sum / self::$count, 2) : 0.00;

        // Log para exibir todas as transações atuais
        Log::info('Transações antes do cálculo de estatísticas', ['transactions' => self::getTransactionsArray()]);

        Log::info('Calculando estatísticas', [
            'sum' => $this->sum,
            'avg' => $avg,
            'max' => $this->max,
            'min' => $this->min,
            'count' => $this->count
        ]);
        // Retornarmos as estatísticas calculadas;
        return [
            'sum' => round(self::$sum, 2),
            'avg' => $avg,
            'max' => round($this->max, 2),
            'min' => round($this->min, 2),
            'count' => self::$count
        ];
    }

    public function cleanUpOldTransactions($expireTime)
    {
        if ($this->transactions->isEmpty()) {
            Log::info('Lista de transações está vazia');
            return;
        } else {
            // Verificação antes do while
            $oldestTransactionTimestamp = Carbon::parse(self::$transactions->bottom()['timestamp']);
            $isExpired = $oldestTransactionTimestamp->lt($expireTime);

            Log::info('Verificação antes do while', [
                'oldest_transaction_timestamp' => $oldestTransactionTimestamp->toDateTimeString(),
                'expire_time' => $expireTime->toDateTimeString(),
                'expired' => $isExpired ? 'Sim' : 'Não',
                'deve_entrar_no_while' => $isExpired ? 'Sim' : 'Não'
            ]);
        }


        while (!self::$transactions->isEmpty() && $isExpired) {
            // Remove a transação mais antiga
            $oldestTransaction = self::$transactions->shift();

            $transactionTimestamp = Carbon::parse($oldestTransaction['timestamp']);
            $currentTimestamp = Carbon::now();
            $isExpired = $transactionTimestamp->lt($expireTime);
    
            Log::info('Transação mais antiga', [
                'timestamp' => $transactionTimestamp->toDateTimeString(),
                'expire_time' => $expireTime->toDateTimeString(),
                'now' => $currentTimestamp->toDateTimeString(),
                'expired' => $isExpired ? 'Sim' : 'Não'
            ]);
        

            // Subtraimos o valor da transação removida
            self::$sum -= $oldestTransaction['amount'];

            // Subtraímos do contador de transações
            self::$count--;

            // Se ainda houver transações, recalculamos o valor mínimo e máximo
            if (self::$count > 0) {
                $this->min = min(array_column(iterator_to_array(self::$transactions), 'amount'));
                $this->max = max(array_column(iterator_to_array(self::$transactions), 'amount'));
            } else {
                // Se não houver mais transações, redefinimos o valor mínimo e máximo
                $this->min = null;
                $this->max = null;
            }
        }

    }

    public function reset()
    {
        $this->transactions = new SplDoublyLinkedList();
        $this->transactions->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
        $this->sum = 0.0;
        $this->count = 0;
        $this->min = null;
        $this->max = null;
        Log::info('Resetando lista de transações e estatísticas');
    }

    // Metódos de acesso público para permitir testarmos
    public function getCount() {
        return self::$count;
    }

    public function getSum() {
        return self::$sum;
    }

    public function getMin() {
        return $this->min;
    }

    public function getMax() {
        return $this->max;
    }


}