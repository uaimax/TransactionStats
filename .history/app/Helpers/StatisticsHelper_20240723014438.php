<?php

namespace App\Helpers;

use Carbon\Carbon;

class StatisticsHelper 
{
    private static $transactions = [];
    private static $sum = 0.0;
    private static $count = 0;
    private static $min = null;
    private static $max = null;

    public static function addTransaction($amount, $timestamp)
    {
        $now = Carbon::now();
        $expireTime = $now->subSeconds(60);

        self::$transactions[] = ['amount' => $amount, 'timestamp' => $timestamp];

        self::$sum += $amount;
        self::$count++;
        self::$min = (self::$min === null || $amount < self::$min) ? $amount : self::$min;
        self::$max = (self::$max === null || $amount > self::$max) ? $amount : self::$max;

        self::cleanUpOldTransactions($expireTime);
    }

    public static function getStatistics()
    {
        $now = Carbon::now();
        $expireTime = $now->subSeconds(60);
        self::cleanUpOldTransactions($expireTime);

        $avg = (self::$count > 0) ? round(self::$sum / self::$count, 2) : 0.00;

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
        self::$transactions = array_filter(self::$transactions, function($transaction) use ($expireTime) {
            if ($transaction['timestamp'] >= $expireTime) {
                return true;
            }

            self::$sum -= $transaction['amount'];
            self::$count--;
            return false;
        });

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
}
