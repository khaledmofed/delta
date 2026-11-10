<?php

namespace App\Providers;

use App\Insurers\InsurerManager;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InsurerManager::class, fn ($app) => new InsurerManager($app, config('insurers.providers', [])));
    }

    public function boot(): void
    {
        // Admin permissions from config/admin.php (role => permissions).
        foreach (config('admin.permissions', []) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
    }
}
