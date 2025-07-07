<?php

namespace App\Infrastructure\Providers;

use App\Domain\Services\AuthService;
use App\Domain\Services\SSNAuthService;
use App\Application\Auth\LoginUserUseCase;
use App\Application\Auth\SSNLoginUseCase;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar servicios de dominio
        $this->app->singleton(AuthService::class);
        $this->app->singleton(SSNAuthService::class);

        // Registrar casos de uso
        $this->app->singleton(LoginUserUseCase::class, function ($app) {
            return new LoginUserUseCase($app->make(AuthService::class));
        });

        $this->app->singleton(SSNLoginUseCase::class, function ($app) {
            return new SSNLoginUseCase($app->make(SSNAuthService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
