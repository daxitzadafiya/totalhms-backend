<?php

namespace App\Providers;

use App\Services\Credit\Service as Credit;
use Illuminate\Support\ServiceProvider;

class CreditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        app()->bind('credit', function () {
            return new Credit;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
