<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation Route - This will be handled by L5-Swagger package
