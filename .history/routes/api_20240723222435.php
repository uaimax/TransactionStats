<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StatisticsController;

Route::post('/transactions', [TransactionController::class, 'store']);
Route::delete('/transactions', [TransactionController::class, 'destroy']);
Route::get('/statistics', [StatisticsController::class, 'index']);
