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
}