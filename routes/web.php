<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegistrationController;
use Illuminate\Support\Facades\Route;

// PUBLIC

Route::get('/', function () {
    return view('home');
})->name('home');

// AUTH

Route::view('/login', 'auth.login')->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::view('/register', 'auth.register')->name('register');
Route::post('/register', [RegistrationController::class, 'store'])
    ->name('register.store');
Route::post('/logout', [LogoutController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
