<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Bootstrap 5 pagination globally (prevents Tailwind SVG oversized arrows)
        Paginator::useBootstrapFive();

        // Force HTTPS in production (fixes browser security warnings and mixed content)
        if($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
