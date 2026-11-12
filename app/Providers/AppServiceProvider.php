<?php

namespace App\Providers;

use App\Insurers\InsurerManager;
use App\Models\User;
use App\Support\JourneyAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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

        // Site header: no customer accounts, so the account area reflects the orders opened in this session.
        View::composer('partials.site.header', function ($view) {
            $orders = rescue(fn () => JourneyAccess::orders(), collect(), false);
            $view->with(['myOrders' => $orders, 'myOrder' => $orders->first()]);
        });
    }
}
