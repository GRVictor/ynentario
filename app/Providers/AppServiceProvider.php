<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Los administradores tienen acceso irrestricto a todos los permisos
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }

            return null;
        });

        // Verificación dinámica según los permisos asociados al usuario
        Gate::after(function (User $user, string $ability, ?bool $result, array $arguments) {
            if ($result !== null) {
                return $result;
            }

            return $user->hasPermission($ability);
        });
    }
}
