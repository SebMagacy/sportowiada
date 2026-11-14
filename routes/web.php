<?php

use App\Http\Controllers\Admin\DisciplineController;
use App\Http\Controllers\Admin\UserController;
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

// DASHBOARD

Route::view('/admin', 'admin.dashboard')->name('admin.dashboard')->middleware(['auth', 'can:access-admin']);

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

Route::get('/admin/disciplines', [DisciplineController::class, 'index'])
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.disciplines.index');
Route::get('/admin/disciplines/create', [DisciplineController::class, 'create'])
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.disciplines.create');
Route::post('/admin/disciplines', [DisciplineController::class, 'store'])
    ->middleware(['auth', 'can:access-admin'])
    ->name('admin.disciplines.store');
