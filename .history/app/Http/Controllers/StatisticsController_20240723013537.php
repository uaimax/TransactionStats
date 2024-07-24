<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function index(): JsonResponse
    {
        $statistics = StatisticsHelper::getStatistics();
        return response
    }
}
