<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SplDoublyLinkedList;

class StatisticsHelper 
{
    // Guardar todas as transações aqui
    private static $transactions;

    // Guardar sobre a soma das transações
    private static $sum = 0.0;

    // Guardar a contagem de transações
    private static $count = 0;

    // Guardamos o menor valor de transação
    private static $min = null;

    // Guardamos o maior valor de transação
    private static $max = null;

    // Método para obter a instância única da classe
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::init();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Previne a instanciação direta
    }

    private function __clone()
    {
        // Previne a clonagem
    }
    
    public static function init()
    {
        Log::info('Foi chamado');
        // Criar lista ligada duplamente
        self::$transactions = new SplDoublyLinkedList();

        // Definição do modo de iteração para FIFO (primeiro a entrar, primeiro a sair)
        self::$transactions->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
    }

    public static function getTransactionsArray() {
        $transactionsArray = [];
        foreach (self::$transactions as $transaction) {
            $transactionsArray[] = $transaction;
        }
        return $transactionsArray;
    }
    

    // Função para adicionar uma nova transação
    public static function addTransaction($amount, $timestamp)
    {
        // Converte o timestamp de string para um objeto Carbon (data/hora)
        $timestamp = Carbon::parse($timestamp);

        // Adiciona nova transação a lista
        self::$transactions->push(['amount' => $amount, 'timestamp' => $timestamp]);

        // Atualizamos as estatísticas
        self::$sum += $amount; // Adicionamos o valor a soma total
        self::$count++; // Incrementamos a quantidade de transações efetuadas
        self::$min = (self::$min === null || $amount < self::$min) ? $amount : self::$min; // Atualização do minimo (menor valor), somente se necessario
        self::$max = (self::$max === null || $amount > self::$max) ? $amount : self::$max; // Atualização do valor maior, somente se necessário

        Log::info('Transação adicionada', [
            'amount' => $amount,
            'timestamp' => $timestamp,
            'sum' => self::$sum,
            'count' => self::$count,
            'min' => self::$min,
            'max' => self::$max
        ]);

        // Log para exibir todas as transações atuais
        Log::info('Transações atuais', ['transactions' => self::getTransactionsArray()]);
        // Retorna true indicando que transação foi criada
        return true;
    }

    public static function getStatistics() {

        // Obtém a data e hora atuais
        $now = Carbon::now();
        $expireTime = $now->subSeconds(60); // Tempo de 60 segundos atrás
        
        // Limpar transações antigas, assim não retorna elas. 
        self::cleanUpOldTransactions($expireTime);
        
        $avg = (self::$count > 0) ? round(self::$sum / self::$count, 2) : 0.00;

        // Log para exibir todas as transações atuais
        Log::info('Transações antes do cálculo de estatísticas', ['transactions' => self::getTransactionsArray()]);

        Log::info('Calculando estatísticas', [
            'sum' => self::$sum,
            'avg' => $avg,
            'max' => self::$max,
            'min' => self::$min,
            'count' => self::$count
        ]);
        // Retornarmos as estatísticas calculadas;
        return [
            'sum' => round(self::$sum, 2),
            'avg' => $avg,
            'max' => round(self::$max, 2),
            'min' => round(self::$min, 2),
            'count' => self::$count
        ];
    }

    public static function cleanUpOldTransactions($expireTime)
    {
        if (self::$transactions->isEmpty()) {
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
                self::$min = min(array_column(iterator_to_array(self::$transactions), 'amount'));
                self::$max = max(array_column(iterator_to_array(self::$transactions), 'amount'));
            } else {
                // Se não houver mais transações, redefinimos o valor mínimo e máximo
                self::$min = null;
                self::$max = null;
            }
        }

    }

    public static function reset()
    {
        self::init();
        self::$sum = 0.0;
        self::$count = 0;
        self::$min = null;
        self::$max = null;
        Log::info('Resetando lista de transações e estatísticas');
    }

    // Metódos de acesso público para permitir testarmos
    public static function getCount() {
        return self::$count;
    }

    public static function getSum() {
        return self::$sum;
    }

    public static function getMin() {
        return self::$min;
    }

    public static function getMax() {
        return self::$max;
    }


}