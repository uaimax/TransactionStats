<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon; //Para manipulação de dados.

class TransactionController extends Controller
{
    // Vamos armazenar as transações em memória
    private $transactions = [];
}