<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Nieprawidłowy adres e-mail lub hasło.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($request->user()?->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return redirect('/');
    }
}
