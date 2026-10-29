<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');
