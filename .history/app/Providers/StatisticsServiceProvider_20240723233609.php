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
        $this->app->singleton(StatisticsHelper::class, function ($app) {
            return StatisticsHelper::getInstance();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        StatisticsHelper::getInstance();
    }
}
