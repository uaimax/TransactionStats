<?php

namespace App\Helpers;

use App\Services\SharedMemoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StatisticsHelper 
{

    // Vou efetuar uma abordagem com arquivo.
    private $filePath;
    private $timeWindow = 60; // Janela de 60 segundos

    public function __construct($filePath = 'transactions.json')
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

    public function reset()
    {
        file_put_contents($this->filePath, json_encode([
            'transactions' => [],
            'sum' => 0.0,
            'count' => 0,
            'max' => PHP_FLOAT_MIN,
            'min' => PHP_FLOAT_MAX
        ]));
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

        // Convertendo o timestamp para inteiro se for um objeto Carbon
        if ($timestamp instanceof \Carbon\Carbon) {
            $timestamp = $timestamp->timestamp;
        }

        // Pegamos o horário atual.
        $now = Carbon::now()->timestamp;
        
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

        // Se não houver transações, retorno estatísticas vazias.
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

    public function cleanUpOldTransactions(&$data)
    {
        // Pegamos o horário atual.
        $now = Carbon::now()->timestamp;
        $updatedMax = false;
        $updatedMin = false;

        // Filtramos as transações, removendo as que são mais antigas que 60 segundos.
        $data['transactions'] = array_filter($data['transactions'], function($transaction) use ($now, &$data, &$updatedMax, &$updatedMin) {
            // Se a transação ainda estiver dentro do tempo válido, mantemos ela.
            if ($transaction['timestamp'] >= $now - $this->timeWindow) {
                return true;
            } else {
                $data['sum'] -= $transaction['amount'];
                $data['count']--;
                
                if ($transaction['amount'] == $data['max']) {
                    $updatedMax = true;
                }
                if ($transaction['amount'] == $data['min']) {
                    $updatedMin = true;
                }

                return false;
            }
        });
        if ($updatedMax || $updatedMin) {
            $this->recalculateMinMax($data);
        }
    }
    

    // Esta função recalcula os valores máximo e mínimo das transações.
    private function recalculateMinMax(&$data)
    {
        if ($data['count'] === 0) {
            $data['max'] = PHP_FLOAT_MIN;
            $data['min'] = PHP_FLOAT_MAX;
            return;
        }

        $data['max'] = max(array_column($data['transactions'], 'amount'));
        $data['min'] = min(array_column($data['transactions'], 'amount'));
    }

}