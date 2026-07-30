<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('http://localhost:5173');
});

Route::get('/login', function () {
    return redirect('http://localhost:5173/auth');
})->name('login');

Route::any('{any}', function () {
    $path = request()->path();
    return redirect('http://localhost:5173/' . ltrim($path, '/'));
})->where('any', '^(?!api|admin|filament).*$');
