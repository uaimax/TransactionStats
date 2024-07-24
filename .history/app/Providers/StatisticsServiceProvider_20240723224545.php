<?php

namespace App\Providers;

use App\Helpers\StatisticsHelper;
use Illuminate\Support\ServiceProvider;

class StatisticsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        StatisticsHelper::init();
    }
}
