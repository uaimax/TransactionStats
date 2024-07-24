<?php

namespace App\Http\Controllers;

use App\Helpers\SemaphoreHelper;
use Illuminate\Http\Request;
use Carbon\Carbon; // Para manipulação de datas.
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{

    protected $semaphorePoolSize = 10; 

    protected function getSemaphoreKey($transactionData)
    {
        // Criação de chave única com base na hora da transação, dividida pelo número total de pools.
        return crc32($transactionData['timestamp']) % $this->semaphorePoolSize;
    }

    // Vamos armazenar as transações em memória
    private static $transactions = [];

    public function store(Request $request){
        Log::info('Iniciando validação dos dados de entrada.');

        // 400 - se o JSON for inválido
        try {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE){
                throw new \Exception('JSON Inválido');
            }
        } catch (\Exception $e) {
            return response()->json([], 400);
        }

        // 422 – se algum dos campos não puder ser analisado ou a data da transação estiver no futuro
        // Definindo as regras de validação
        $rules = [
            'amount' => 'required|numeric',
            'timestamp' => 'required|date_format:Y-m-d\TH:i:s.v\Z'
        ];

        // Validando a requisição
        // 422 – [se algum dos campos não puder ser analisado] 
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return response()->json([], 422);
        };
        
        // Adicionando agora a lógica de validação para o timestamp
        // Converto o timestamp para o formato Carbon
        $timestamp = Carbon::parse($data['timestamp']);
        Log::info('Timestamp convertido.', ['timestamp' => $timestamp->format('Y-m-d\TH:i:s.v\Z')]);
        
        // 422 – [... ou a data da transação estiver no futuro]
        if($request->has('timestamp')) {
            if($timestamp->isFuture()){
                Log::error('Timestamp no futuro.', ['timestamp' => $request->input('timestamp')]);
                return response()->json([], 422);
            }
        }

        Log::info('Dados validados com sucesso.', ['data' => $data]);    
        
        // Verificando se a transação é mais antiga que 60 segundos
        // 204 – se a transação for mais antiga que 60 segundos
        //if($timestamp->diffInSeconds(now()) > 60) {
        //    Log::info('Transação mais antiga que 60 segundos.', ['timestamp' => $timestamp]);
            // 204 – se a transação for mais antiga que 60 segundos
        //    return response()->json([], 204); 
        //}
        
        // Obtemos a chave do semáforo
        $semaphoreKey = $this->getSemaphoreKey($data);
        Log::info('Semaforo', [$semaphoreKey]);

        try {
            SemaphoreHelper::Semaphore($semaphoreKey, function() use ($data, $timestamp) {

                
            }
        } catch (\Exception $e) {
            
        }
        // 201 – em caso de sucesso
        //return response()->json([], 201);
    }

}
