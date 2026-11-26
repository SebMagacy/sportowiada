<?php

namespace App\Providers;

use App\Filament\Auth\LoginResponse;
use App\Filament\Auth\RegistrationResponse;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);

        $this->app->bind(
            RegistrationResponseContract::class,
            RegistrationResponse::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(
            fn (): Password => Password::min(15)->uncompromised(),
        );
    }
}
