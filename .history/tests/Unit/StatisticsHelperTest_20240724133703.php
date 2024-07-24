<?php

use PHPUnit\Framework\TestCase;

class StatisticsHelperTest extends TestCase
{
    protected $statisticsHelper;

    // Esta função é executada antes de cada teste para configurar o ambiente de teste.
    public function setUp(): void
    {
        // Chamamos a função setUp da classe pai (TestCase) para garantir que qualquer configuração
        // adicional necessária pela estrutura de testes seja realizada.
        parent::setUp();

        // Criamos uma nova instância de StatisticsHelper para cada teste.
        $this->statisticsHelper = new StatisticsHelper();

        // Resetamos o estado da instância, limpando qualquer dado ou estado anterior.
        // Isso garante que cada teste comece com um estado limpo e previsível.
        $this->statisticsHelper->reset();
    }

    // Outros métodos de teste seguirão aqui...
}
