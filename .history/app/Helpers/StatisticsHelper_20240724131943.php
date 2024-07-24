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

        // Carregamos os dados atuais do arquivo.
        $data = $this->loadData();
        
        // Limpamos as transações antigas.
        $this->cleanUpOldTransactions($data);

        // Se não houver transações, retornamos estatísticas vazias.
        if ($data['count'] === 0) {
            return [
                'sum' => '0.00',
                'avg' => '0.00',
                'max' => '0.00',
                'min' => '0.00',
                'count' => 0
            ];
        }

        // Calculamos a média das transações.
        $avg = $data['sum'] / $data['count'];
        
        // Retornamos as estatísticas formatadas.
        return [
            'sum' => number_format($data['sum'], 2, '.', ''),
            'avg' => number_format($avg, 2, '.', ''),
            'max' => number_format($data['max'], 2, '.', ''),
            'min' => number_format($data['min'], 2, '.', ''),
            'count' => $data['count']
        ];
    }

    public function cleanUpOldTransactions($expireTime)
    {
        // Pegamos o horário atual.
        $now = time();

        // Filtramos as transações, removendo as que são mais antigas que 60 segundos.
        $data['transactions'] = array_filter($data['transactions'], function($transaction) use ($now, &$data) {
            // Se a transação ainda estiver dentro do tempo válido, mantemos ela.
            if ($transaction['timestamp'] >= $now - $this->timeWindow) {
                return true;
            } else {
                // Se a transação for muito antiga, atualizamos as estatísticas.
                $data['sum'] -= $transaction['amount'];
                $data['count']--;
                return false;
            }
        });

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