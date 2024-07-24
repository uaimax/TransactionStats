<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
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

    public static function init()
    {
        // Criar lista ligada duplamente
        self::$transactions = new SplDoublyLinkedList();

        // Definição do modo de iteração para FIFO (primeiro a entrar, primeiro a sair)
        self::$transactions->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
    }


    // Função para adicionar uma nova transação
    public static function addTransaction($amount, $timestamp)
    {
        // Converte o timestamp de string para um objeto Carbon (data/hora)
        $timestamp = Carbon::parse($timestamp);

        $now = Carbon::now(); // Hora atual
        $expireTime = $now->subSeconds(60); // Quanto era 60 segundos atrás


        // Verifica se o timestamp da transação é mais antigo que 60 segundos
        if ($timestamp < $expireTime) {
            // Retorna resposta HTTP 204 (sem conteúdo) porque a transação é muito antiga
            throw new InvalidArgumentException('Transação tem mais de 60 segundos');
        }

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
        // Limpamos transações expiradas
        self::cleanUpOldTransactions($expireTime);

        // Retorna true indicando que transação foi criada
        return true;
    }

    public static function getStatistics() {

        // Obtém a data e hora atuais
        $now = Carbon::now();
        $expireTime = (clone $now)->subSeconds(60); // Tempo de 60 segundos atrás
        
        // Limpar transações antigas, assim não retorna elas. 
        self::cleanUpOldTransactions($expireTime);
        
        $avg = (self::$count > 0) ? round(self::$sum / self::$count, 2) : 0.00;

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

        Log::info('Iniciando limpeza de transações antigas.', ['expireTime' => $expireTime->toDateTimeString()]);

        if (self::$transactions->isEmpty()) {
            Log::info('Lista de transações está vazia');
        } else {
            Log::info('Lista de transações não está vazia', ['transaction_count' => self::$transactions->count()]);
            Log::info('Transação mais antiga na lista', ['oldest_transaction' => self::$transactions->bottom()]);
        }
    
        while (!self::$transactions->isEmpty() && self::$transactions->bottom()['timestamp']->lt($expireTime)) {
            Log::info('até aqui chega');
            // Remove a transação mais antiga
            $oldestTransaction = self::$transactions->shift();
            Log::info('Verificando transação mais antiga', ['oldestTransaction' => $oldestTransaction, 'expireTime' => $expireTime]);

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

// Inicializa a lista de transações quando a classe é carregada
StatisticsHelper::init();