<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Auth\Authenticatable;

class Login extends BaseLogin
{
    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        return true;
    }
}
