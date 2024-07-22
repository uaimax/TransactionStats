<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon; // Para manipulação de datas.

class TransactionController extends Controller
{
    // Vamos armazenar as transações em memória
    private static $transactions = [];

    public function store(Request $request){
        // Adicionando agora a lógica de validação e armazenando da transação
        $data = $request->validate([
            'amount' => 'required|numeric', // campo amount sendo numérico
            'timestamp' => 'required|date_format:Y-m-d\TH:i:s.u\Z|before_or_equal:now'
        ])
    }

}
