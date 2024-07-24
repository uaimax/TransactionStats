<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon; // Para manipulação de datas.
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    // Vamos armazenar as transações em memória
    private static $transactions = [];

    public function store(Request $request){
        Log::info('Iniciando validação dos dados de entrada.');


        // Definindo as regras de validação
        $rules = [
            'amount' => 'required|numeric',
            'timestamp' => 'required|date_format:Y-m-d\TH:i:s.v\Z'
        ];

        // Validando a requisição
        $validator = Validator::make($request->all(), $rules);

        if($validator)

        // Adicionando agora a lógica de validação 
        if($request->has('timestamp')) {
            $timestamp = Carbon::parse($request->input('timestamp'));
            if($timestamp->isFuture()){
                Log::error('Timestamp no futuro.', ['timestamp' => $request->input('timestamp')]);
                return response()->json(['errors' => ['timestamp' => ['Timestamp está no futuro. 422 – se algum dos campos não puder ser analisado ou a data da transação estiver no futuro']]], 422);
            }
        }

        try {
            // Validação dos dados de entrada
            $data = $request->validate([
                'amount' => 'required|numeric',
                'timestamp' => 'required|date_format:Y-m-d\TH:i:s.v\Z|before_or_equal:now',
            ]);
        } catch (ValidationException $e) {
            Log::error('Erro na validação dos dados.', ['errors' => $e->errors()]);
            
            
            return response()->json(['errors' => $e->errors()], 400);
        }

        Log::info('Dados validados com sucesso.', ['data' => $data]);

        // Converto o timestamp para o formato Carbon
        $timestamp = Carbon::parse($data['timestamp']);
        Log::info('Timestamp convertido.', ['timestamp' => $timestamp->format('Y-m-d\TH:i:s.v\Z')]);
        
        // Verificando se a transação é mais antiga que 60 segundos
        if($timestamp->diffInSeconds(now()) > 60) {
            Log::info('Transação mais antiga que 60 segundos.', ['timestamp' => $timestamp]);
            // 204 – se a transação for mais antiga que 60 segundos
            return response()->json([], 204); 
        }
        
        self::$transactions[] = $data;
        Log::info('Transação armazenada.', ['timestamp' => $timestamp]);
        // 201 – em caso de sucesso
        return response()->json([], 201);
    }

}
