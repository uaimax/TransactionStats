<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StatisticsController;

Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/statistics', [StatisticsController::class, 'index']);
