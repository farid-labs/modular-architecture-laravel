<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    return response()->json([
        'service' => 'laravel-modular-monolith-api',
        'status' => 'ok',
        'version' => config('app.version', 'dev'),
        'time' => now()->toIso8601String(),
    ]);
});