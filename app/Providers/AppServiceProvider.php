<?php

namespace App\Providers;

use App\Insurers\InsurerManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InsurerManager::class, fn ($app) => new InsurerManager($app, config('insurers.providers', [])));
    }

    public function boot(): void
    {
        //
    }
}
