<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('/admin', 'admin.dashboard')->name('admin.dashboard')->middleware(['auth', 'can:access-admin']);
Route::view('/login', 'auth.login')->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::view('/register', 'auth.register')->name('register');
Route::post('/register', [RegistrationController::class, 'store'])
    ->name('register.store');
Route::post('/logout', [LogoutController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// USERS

Route::get('/admin/users', [UserController::class, 'index'])
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.users.index');
Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.users.edit');
Route::patch('/admin/users/{user}', [UserController::class, 'update'])
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.users.update');

// DISCIPLINES

Route::view('/admin/disciplines', 'admin.disciplines.index')
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.disciplines.index');
