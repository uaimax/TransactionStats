<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon; // Para manipulação de datas.

class TransactionController extends Controller
{
    // Vamos armazenar as transações em memória
    private static $transactions = [];

    // Adicionando agora a lógica de validação e armazenando da transação
    public function store(Request, $request) {
        
    }

}
