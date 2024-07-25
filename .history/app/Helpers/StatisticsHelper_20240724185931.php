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
        }
    
        // Carrega os dados da memória compartilhada ou do arquivo.
        $this->loadData();
    
        // Registra a função para ser chamada na finalização da aplicação.
        register_shutdown_function([$this, 'syncDataToFile']);
    }

    public function reset()
    {
        $data = [
            'transactions' => array_fill(0, $this->timeWindow, []),
            'sum' => 0.0,
            'count' => 0,
            'max' => PHP_FLOAT_MIN,
            'min' => PHP_FLOAT_MAX
        ];

        apcu_store('statistics_data', $data);

        $this->transactions = $data['transactions'];
        $this->sum = $data['sum'];
        $this->count = $data['count'];
        $this->max = $data['max'];
        $this->min = $data['min'];
        file_put_contents($this->filePath, json_encode($data)); // Salva os dados no arquivo.
    }

    public function syncDataToFile()
    {
        $data = [
            'transactions' => $this->transactions,
            'sum' => $this->sum,
            'count' => $this->count,
            'max' => $this->max,
            'min' => $this->min
        ];

        file_put_contents(storage_path('app/transactions.json'), json_encode($data)); // Salva os dados no arquivo.
    }

    // Esta função carrega os dados do arquivo.
    private function loadData()
    {
        if (apcu_exists('statistics_data')) {
            $data = apcu_fetch('statistics_data'); // Carrega os dados da memória compartilhada.
        } else {
            $data = json_decode(file_get_contents($this->filePath), true); // Carrega os dados do arquivo.
    
            // Verifica se os dados foram carregados corretamente e inicializa variáveis se necessário
            if (is_null($data) || !isset($data['transactions']) || !isset($data['sum']) || !isset($data['count']) || !isset($data['max']) || !isset($data['min'])) {
                $this->reset(); // Reseta os dados se não estiverem no formato esperado.
                return;
            }
    
            apcu_store('statistics_data', $data); // Salva os dados na memória compartilhada.
        }
    
        $this->transactions = $data['transactions'];
        $this->sum = $data['sum'];
        $this->count = $data['count'];
        $this->max = $data['max'];
        $this->min = $data['min'];
    }

    // Esta função salva os dados no arquivo.
    private function saveData()
    {
        $data = [
            'transactions' => $this->transactions,
            'sum' => $this->sum,
            'count' => $this->count,
            'max' => $this->max,
            'min' => $this->min
        ];
    
        apcu_store('statistics_data', $data); // Salva os dados na memória compartilhada.
        Log::info('Dados armazenados na memória compartilhada.');
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
    
        // Calcula o índice do minuto dentro da janela de tempo.
        $minuteIndex = intdiv($timestamp, 60) % $this->timeWindow;
    
    
        // Cria um array representando a transação.
        $transaction = [
            'amount' => (float)$amount,
            'timestamp' => $timestamp
        ];
    
        // Adiciona a transação na lista correspondente ao índice.
        $this->transactions[$minuteIndex][] = $transaction;
        
        // Atualiza a soma, contagem, máximo e mínimo.
        $this->sum += $transaction['amount'];
        $this->count++;
        $this->max = max($this->max, $transaction['amount']);
        $this->min = min($this->min, $transaction['amount']);
    
        // Limpamos as transações antigas.
        $this->cleanUpOldTransactions();
    
        // Salvamos os dados atualizados de volta na memória.
        $this->saveData();
    
        return 201; // Sucesso
    }
    

    public function getStatistics() {

        // Carregamos os dados atuais do arquivo.
        $data = $this->loadData();
        
        // Limpamos as transações antigas.
        $this->cleanUpOldTransactions();


        // Se não houver transações, retorno estatísticas vazias.
        if ($this->count === 0) {
            return [
                'sum' => '0.00',
                'avg' => '0.00',
                'max' => '0.00',
                'min' => '0.00',
                'count' => 0
            ];
        }

        // Calculamos a média das transações.
        $avg = $this->sum / $this->count;
        
        // Retornamos as estatísticas formatadas.
        return [
            'sum' => number_format($this->sum, 2, '.', ''),
            'avg' => number_format($avg, 2, '.', ''),
            'max' => number_format($this->max, 2, '.', ''),
            'min' => number_format($this->min, 2, '.', ''),
            'count' => $this->count
        ];
    }

    public function cleanUpOldTransactions()
    {
        // Verifica se há transações no índice atual e se são antigas
        if ($this->timestamps[$currentMinuteIndex] !== null && $this->timestamps[$currentMinuteIndex] < $now - $this->timeWindow) {
            // Remover a soma e a contagem da transação antiga
            foreach ($this->transactions[$currentMinuteIndex] as $oldTransaction) {
                $this->sum -= $oldTransaction['amount'];
                $this->count--;
            }

            // Resetar o índice atual
            $this->transactions[$currentMinuteIndex] = [];
            $this->timestamps[$currentMinuteIndex] = $now;

            // Recalcular o mínimo e máximo se necessário
            $this->recalculateMinMax();
        }
    
        // Salvamos os dados atualizados de volta na memória.
        $this->saveData();
    }
    
    

    // Recalcula o valor mínimo e máximo.
    private function recalculateMinMax()
    {
        $this->max = PHP_FLOAT_MIN;
        $this->min = PHP_FLOAT_MAX;
    
        foreach ($this->transactions as $transactions) {
            foreach ($transactions as $transaction) {
                $this->max = max($this->max, $transaction['amount']);
                $this->min = min($this->min, $transaction['amount']);
            }
        }
    }

    

}