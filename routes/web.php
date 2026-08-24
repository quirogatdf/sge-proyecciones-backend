<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'timestamp' => now()->toISOString()]);
});

