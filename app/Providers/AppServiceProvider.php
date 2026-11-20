<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        if (! defined('API_VERSION')) {
            define('API_VERSION', config('app.version', '1.0.0'));
        }

        if (! defined('APP_NAME')) {
            define('APP_NAME', config('app.name'));
        }

        if (! defined('APP_URL')) {
            define('APP_URL', config('app.url').'/api');
        }
    }
}
