<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'NannyLink API',
        'status' => 'online',
        'admin_panel' => url('/admin'),
    ]);
});

Route::get('/login', function () {
    if (request()->wantsJson()) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
    return redirect('/admin/login');
})->name('login');

Route::any('{any}', function () {
    $path = request()->path();
    return response()->json([
        'message' => 'NannyLink Backend API',
        'path' => $path,
    ]);
})->where('any', '^(?!api|admin|filament).*$');
