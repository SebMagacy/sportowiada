<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\RegistrationResponse as BaseRegistrationResponse;
use Illuminate\Http\RedirectResponse;

class RegistrationResponse extends BaseRegistrationResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('home');
    }
}
