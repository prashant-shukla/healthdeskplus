<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Password reset routes (required for Laravel's password reset functionality)
Route::get('password/reset/{token}', function ($token) {
    return response()->json([
        'message' => 'Password reset page - use the API endpoint instead',
        'token' => $token
    ]);
})->name('password.reset');

Route::post('password/reset', function () {
    return response()->json([
        'message' => 'Use the API endpoint /api/auth/reset-password instead'
    ]);
})->name('password.update');

// API Documentation Route - This will be handled by L5-Swagger package
