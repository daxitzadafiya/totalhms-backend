<?php

namespace App\Providers;

use App\Services\CarouselService;
use App\Services\NavbarMessageService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Services\NavbarNotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('navbar.messages', NavbarMessageService::class);

        $this->app->singleton('navbar.notifications', NavbarNotificationService::class);

        $this->app->singleton('carousel', CarouselService::class);
    }
}
