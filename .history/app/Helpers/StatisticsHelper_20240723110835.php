<?php

namespace App\Helpers;

use Carbon\Carbon;

class StatisticsHelper 
{
    // Guardar todas as transações aqui
    private static $transactions = [];

    // Guardamos a soma de todas as transações
    private static $sum = 0.0;

    // Guardamos a quantidade de transações
    private static $count = 0;

    // Guardamos o menor valor de transação
    private static $min = null;

    // Guardamos o maior valor de transação
    private static $max = null;

    // Função para adicionar uma nova transação
    public static function addTransaction($amount, $timestamp)
    {
        $now = Carbon::now(); // Hora atual
        $expireTime = $now->subSeconds(60); // Quanto era 60 segundos atrás

        // Adicionamos nova transação a lista
        self::$transactions[] = ['amount' => $amount, 'timestamp' => $timestamp];

        // Atualizamos as estatísticas
        self::$sum += $amount; // Adicionamos o valor a soma total
        self::$count++; // Incrementamos a quantidade de transações efetuadas
        self::$min = (self::$min === null || $amount < self::$min) ? $amount : self::$min; // Atualização do minimo (menor valor), somente se necessario
        self::$max = (self::$max === null || $amount > self::$max) ? $amount : self::$max; // Atualização do valor maior, somente se necessário

        // Limpamos transações expiradas
        self::cleanUpOldTransactions($expireTime);
    }

    public static function getStatistics() {
        $now = Carbon::now();
        $expireTime = $now->subSeconds(60); // Tempo de 60 segundos atrás

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
        // Filtrar transações para manter apenas as mais recentes
        self::$transactions = array_filter(self::$transactions, function($transaction) use ($expireTime) {
            // Conferir se a transação ainda é válida
            if ($transaction['timestamp'] >= $expireTime){
                return true;
            }
            // Se a transação já tiver de ser expirada, removo seu valor
            self::$sum -= $transaction['amount'];
            self::$count--;
            return false;
        });

        // Após alterações, recalculo o menor e maior valor
        if (self::$count > 0) {
            self::$min = min(array_column(self::$transactions, 'amount'));
            self::$max = max(array_column(self::$transactions, 'amount'));
        } else {
            self::$min = null;
            self::$max = null;
        }

    }

    public static function reset()
    {
        self::$transactions = [];
        self::$sum = 0.0;
        self::$count = 0;
        self::$min = null;
        self::$max = null;
    }

    // Metódos de acesso público para permitir testarmos
    public static function getCount() {
        return self::$count;
    }

    public static function getCount() {
        return self::$count;
    }

    public static function getCount() {
        return self::$count;
    }

    public static function getCount() {
        return self::$count;
    }

    public static function getCount() {
        return self::$count;
    }

}