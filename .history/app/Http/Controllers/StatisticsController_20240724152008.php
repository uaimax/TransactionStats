<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    public function index(): JsonResponse
    {
    Log::info('Chamando o método destroy para resetar as transações.');
    $statisticsHelper = new StatisticsHelper();
    $statisticsHelper->reset();
    return response()->json([], 204);
        return response()->json($statistics);
    }
}