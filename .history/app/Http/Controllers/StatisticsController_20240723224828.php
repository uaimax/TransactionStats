<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    public function index(): JsonResponse
    {
        
        $statistics = StatisticsHelper::getStatistics();
        Log::info('Estatísticas calculadas: ', $statistics);
        return response()->json($statistics);
    }
}
