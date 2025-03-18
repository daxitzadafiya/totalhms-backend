<?php

namespace App\Providers;

use App\Services\Strex\Service as Strex;
use Illuminate\Support\ServiceProvider;

class StrexServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        app()->bind('strex', function () {
            return new Strex;
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
