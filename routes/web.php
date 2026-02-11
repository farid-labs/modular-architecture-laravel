<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    return response()->json([
        'service' => 'modular-architecture-laravel-api',
        'status' => 'ok',
        'version' => config('app.version', 'dev'),
        'time' => now()->toIso8601String(),
    ]);
});
