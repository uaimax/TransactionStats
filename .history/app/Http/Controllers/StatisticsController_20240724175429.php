<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    public function getStatisticsHelper()
    {
        $statisticsHelper = new StatisticsHelper();
        $statistics = $statisticsHelper->getStatistics();

        return response()->json($statistics);
    }


    public function index(): JsonResponse
    {
        $statisticsHelper = new StatisticsHelper();
        $statistics = $statisticsHelper->getStatistics();
        Log::info('Estatísticas calculadas: ', $statistics);
        return response()->json($statistics, 201); // Retorna 201 para GET /statistics conforme especificações
    }
}
