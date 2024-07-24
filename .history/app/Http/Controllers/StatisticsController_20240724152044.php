<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use Illuminate\Http\JsonResponse;

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
    og::info('Chamando o método destroy para resetar as transações.');
    $statisticsHelper = new StatisticsHelper();
    $statisticsHelper->reset();
    return response()->json([], 204);
        return response()->json($statistics);
    }
}
