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
    private $transactions = [];  // Lista de transações.
    private $sum = 0.0;  // Soma dos valores das transações.
    private $count = 0;  // Número de transações.
    private $max = PHP_FLOAT_MIN;  // Valor máximo das transações.
    private $min = PHP_FLOAT_MAX;  // Valor mínimo das transações.

    public function __construct($filePath = 'transactions.json')
    {
        // Define o caminho do arquivo.
        $this->filePath = $filePath;
        // Se o arquivo não existir, reseta os dados.
        if (!file_exists($this->filePath)) {
            $this->reset();
        }        } else {
            $this->loadData();
        }

    }

    public function reset()
    {
        // Salva um array inicial vazio no arquivo.
        file_put_contents($this->filePath, json_encode([
            'transactions' => array_fill(0, $this->timeWindow, []),  // Cria uma lista de listas vazias.
            'sum' => 0.0,  // Reseta a soma.
            'count' => 0,  // Reseta a contagem.
            'max' => PHP_FLOAT_MIN,  // Reseta o máximo.
            'min' => PHP_FLOAT_MAX  // Reseta o mínimo.
        ]));
        // Reseta as variáveis da classe.
        $this->transactions = array_fill(0, $this->timeWindow, []);
        $this->sum = 0.0;
        $this->count = 0;
        $this->max = PHP_FLOAT_MIN;
        $this->min = PHP_FLOAT_MAX;
    }

    
    // Esta função carrega os dados do arquivo.
    private function loadData()
    {
        $data = json_decode(file_get_contents($this->filePath), true);
        $this->transactions = $data['transactions'];
        $this->sum = $data['sum'];
        $this->count = $data['count'];
        $this->max = $data['max'];
        $this->min = $data['min'];
    }

    // Esta função salva os dados no arquivo.
    private function saveData()
    {
        file_put_contents($this->filePath, json_encode([
            'transactions' => $this->transactions,
            'sum' => $this->sum,
            'count' => $this->count,
            'max' => $this->max,
            'min' => $this->min
        ]));
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

        // Calcula o índice do segundo dentro da janela de tempo.
        $secondIndex = $timestamp % $this->timeWindow;

        // Cria um array representando a transação.
        $transaction = [
            'amount' => (float)$amount,
            'timestamp' => $timestamp
        ];

        // Adiciona a transação na lista correspondente ao índice.
        $this->transactions[$secondIndex][] = $transaction;
        
        // Atualiza a soma, contagem, máximo e mínimo.
        $this->sum += $transaction['amount'];
        $this->count++;
        $this->max = max($this->max, $transaction['amount']);
        $this->min = min($this->min, $transaction['amount']);

        // Limpamos as transações antigas.
        $this->cleanUpOldTransactions();

        // Salvamos os dados atualizados de volta no arquivo.
        $this->saveData();

        return 201; // Sucesso
    }

    public function getStatistics() {

        // Carregamos os dados atuais do arquivo.
        $data = $this->loadData();
        
        // Limpamos as transações antigas.
        $this->cleanUpOldTransactions();

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

    public function cleanUpOldTransactions()
    {
        // Pega o timestamp atual e o timestamp mais antigo permitido.
        $now = Carbon::now()->timestamp;
        $oldestTimestamp = $now - $this->timeWindow;

        // Itera sobre as listas de transações.
        foreach ($this->transactions as $secondIndex => $transactions) {
            // Itera sobre as transações dentro de cada lista.
            foreach ($transactions as $key => $transaction) {
                // Se a transação for antiga, remove-a.
                if ($transaction['timestamp'] < $oldestTimestamp) {
                    $this->sum -= $transaction['amount'];
                    $this->count--;
                    unset($this->transactions[$secondIndex][$key]);
                }
            }
            // Reorganiza a lista para remover buracos.
            $this->transactions[$secondIndex] = array_values($this->transactions[$secondIndex]);
        }
        // Se ainda houver transações, recalcula o mínimo e o máximo.
        if ($this->count > 0) {
            $this->recalculateMinMax();
        } else {
            // Caso contrário, reseta o máximo e o mínimo.
            $this->max = PHP_FLOAT_MIN;
            $this->min = PHP_FLOAT_MAX;
        }
    }
    

    // Recalcula o valor mínimo e máximo.
    private function recalculateMinMax()
    {
        // Reseta o máximo e o mínimo.
        $this->max = PHP_FLOAT_MIN;
        $this->min = PHP_FLOAT_MAX;

        // Itera sobre todas as transações para encontrar o novo máximo e mínimo.
        foreach ($this->transactions as $transactions) {
            foreach ($transactions as $transaction) {
                $this->max = max($this->max, $transaction['amount']);
                $this->min = min($this->min, $transaction['amount']);
            }
        }
    }

}