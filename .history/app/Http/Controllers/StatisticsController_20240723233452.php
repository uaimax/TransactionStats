<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    public function index(): JsonResponse
    {
        $statistics = StatisticsHelper::getInstance()->getStatistics;
        return response()->json($statistics);
    }
}
