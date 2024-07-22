<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon; // Para manipulação de datas.
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    // Vamos armazenar as transações em memória
    private static $transactions = [];

    public function store(Request $request){
        Log::info('Iniciando validação dos dados de entrada.');

        // Adicionando agora a lógica de validação e armazenando da transação
        
        try {
            // Validação dos dados de entrada
            $data = $request->validate([
                'amount' => 'required|numeric',
                'timestamp' => 'required|date_format:Y-m-d\TH:i:s.v\Z|before_or_equal:now',
            ]);
        } catch (ValidationException $e) {
            Log::error('Erro na validação dos dados.', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }

        Log::info('Dados validados com sucesso.', ['data' => $data]);

        // Converto o timestamp para o formato Carbon
        $timestamp = Carbon::parse($data['timestamp']);
        Log::info('Timestamp convertido.', ['timestamp' => $timestamp->format('Y-m-d\TH:i:s.v\Z')]);
        
        // Verificando se a transação é mais antiga que 60 segundos
        if($timestamp->diffInSeconds(now()) > 60) {
            Log::info('Transação mais antiga que 60 segundos.', ['timestamp' => $timestamp]);
            return response()->json([], 204); //Responde com 204 (No content)
        }
        
        self::$transactions[] = $data;
        Log::info('Transação mais antiga que 60 segundos.', ['timestamp' => $timestamp]);
        // Retornando resposta de sucesso
        return response()->json([], 201);
    }

}
