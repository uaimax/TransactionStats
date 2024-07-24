<?php

namespace App\Helpers;

use App\Services\SharedMemoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SplDoublyLinkedList;

class StatisticsHelper 
{

    // Vou efetuar uma abordagem com arquivo.
    private $filePath;
    private $timeWindow = 60; // Janela de 60 segundos

    private function __construct($filePath = 'transactions.json')
    {
        // Guardo caminho do arquivo em uma váriavel
        $this->filePath = $filePath;

        // Se o arquivo ainda não existir, criamos ele com dados iniciais.
        if (!file_exists($this->filePath)) {
            // Salvamos um arquivo JSON com uma lista de transações vazias e outras estatísticas.
            file_put_contents($this->filePath, json_encode([
                'transactions' => [],
                'sum' => 0.0,
                'count' => 0,
                'max' => PHP_FLOAT_MIN,
                'min' => PHP_FLOAT_MAX
            ]));
        }


    }
    // Esta função carrega os dados do arquivo.
    private function loadData()
    {
        // Lemos o conteúdo do arquivo e transformamos de JSON para um array PHP.
        return json_decode(file_get_contents($this->filePath), true);
    }

    // Esta função salva os dados no arquivo.
    private function saveData($data)
    {
        // Transformamos os dados em JSON e salvamos no arquivo.
        file_put_contents($this->filePath, json_encode($data));
    }
    // Função para adicionar uma nova transação
    public function addTransaction($amount, $timestamp)
    {
        // Carregamos os dados atuais do arquivo.
        $data = $this->loadData();

        // Pegamos o horário atual.
        $now = time();
        
        // Se a transação for muito antiga, retornamos um código de erro.
        if ($timestamp < $now - $this->timeWindow) {
            return 204; // Transação mais antiga que 60 segundos
        }
        
        // Se a transação estiver no futuro, retornamos outro código de erro.
        if ($timestamp > $now) {
            return 422; // Data da transação está no futuro
        }

        // Criamos um array para a nova transação.
        $transaction = [
            'amount' => (float)$amount,
            'timestamp' => $timestamp
        ];

        // Adicionamos a nova transação na lista de transações.
        array_push($data['transactions'], $transaction);
        
        // Atualizamos as estatísticas.
        $data['sum'] += $transaction['amount']; // Soma
        $data['count']++; // Contagem
        $data['max'] = max($data['max'], $transaction['amount']); // Máximo
        $data['min'] = min($data['min'], $transaction['amount']); // Mínimo

        // Limpamos as transações antigas.
        $this->cleanUpOldTransactions($data);

        // Salvamos os dados atualizados de volta no arquivo.
        $this->saveData($data);

        return 201; // Sucesso
    }

    public function getStatistics() {

        // Obtém a data e hora atuais
        $now = Carbon::now();
        $expireTime = $now->subSeconds(60); // Tempo de 60 segundos atrás
        
        // Limpar transações antigas, assim não retorna elas. 
        $this->cleanUpOldTransactions($expireTime);
        
        $avg = ($this->count > 0) ? round($this->sum / $this->count, 2) : 0.00;

        // Log para exibir todas as transações atuais
        Log::info('Transações antes do cálculo de estatísticas', ['transactions' => $this->getTransactionsArray()]);

        Log::info('Calculando estatísticas', [
            'sum' => $this->sum,
            'avg' => $avg,
            'max' => $this->max,
            'min' => $this->min,
            'count' => $this->count
        ]);
        // Retornarmos as estatísticas calculadas;
        // Um dos requisitos é o HALF_ROUND_UP, no entanto o PHP já utiliza arredondamento para cima com a função number_format.
        return [
            'sum' => number_format($this->sum, 2, '.', ''),
            'avg' => number_format($avg, 2, '.', ''),
            'max' => number_format($this->max, 2, '.', ''),
            'min' => number_format($this->min, 2, '.', ''),
            'count' => $this->count
        ];
    }

    public function cleanUpOldTransactions($expireTime)
    {
        while (!$this->transactions->isEmpty()) {
            $transaction = $this->transactions->bottom();
    
            // Verifica se a transação contém a chave 'timestamp'
            if (!isset($transaction['timestamp'])) {
                Log::warning('Transação sem timestamp encontrada', ['transaction' => $transaction]);
                $this->transactions->shift(); // Remove a transação inválida
                continue; // Continua com a próxima iteração
            }
    
            $transactionTimestamp = Carbon::parse($transaction['timestamp']);
    
            if ($transactionTimestamp->lt($expireTime)) {
                $removedTransaction = $this->transactions->shift();
                $this->sum -= $removedTransaction['amount'];
                $this->count--;
    
                // Recalcular min e max somente se a transação removida afetar esses valores
                if ($this->count > 0) {
                    if ($removedTransaction['amount'] == $this->min) {
                        $this->min = min(array_column(iterator_to_array($this->transactions), 'amount'));
                    }
                    if ($removedTransaction['amount'] == $this->max) {
                        $this->max = max(array_column(iterator_to_array($this->transactions), 'amount'));
                    }
                } else {
                    $this->min = null;
                    $this->max = null;
                }
            } else {
                break;
            }
        }
    
        // Escrever transações atualizadas na memória compartilhada
        $this->memoryService->write(iterator_to_array($this->transactions));
        Log::info('Transações após a limpeza', ['transactions' => iterator_to_array($this->transactions)]);
    }
    

    public function reset()
    {
        
        // Limpa a memória compartilhada
        $this->memoryService->delete();

        // Redefine o serviço de memória compartilhada
        $this->memoryService = new SharedMemoryService($this->memoryKey);

        // Redefine a lista de transações e estatísticas
        $this->transactions = new SplDoublyLinkedList();
        $this->transactions->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
        $this->sum = 0.0;
        $this->count = 0;
        $this->min = null;
        $this->max = null;

        // Persistir o estado limpo na memória compartilhada
        $this->memoryService->write(iterator_to_array($this->transactions));
        Log::info('Resetando lista de transações e estatísticas');
    }

    // Metódos de acesso público para permitir testarmos
    public function getCount() {
        return $this->count;
    }

    public function getSum() {
        return $this->sum;
    }

    public function getMin() {
        return $this->min;
    }

    public function getMax() {
        return $this->max;
    }


}