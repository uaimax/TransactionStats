<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    public function index(): JsonResponse
    {
        LogLog::info('Estatísticas calculadas: ', $statistics);
        $statistics = StatisticsHelper::getStatistics();
        return response()->json($statistics);
    }
}
