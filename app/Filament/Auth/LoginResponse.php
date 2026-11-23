<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Auth\Http\Responses\LoginResponse as BaseLoginResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse extends BaseLoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if ($user instanceof User && ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            return redirect()->to('/');
        }

        return parent::toResponse($request);
    }
}
