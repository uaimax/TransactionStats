<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon

class TransactionController extends Controller
{
    // Vamos armazenar as transações em memória
    private $transactions = [];
}
