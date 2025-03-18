<?php

namespace App\Providers;

use App\Services\Fiken\Service as Fiken;
use Illuminate\Support\ServiceProvider;

class FikenServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        app()->bind('fiken', function () {
            return new Fiken;
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
