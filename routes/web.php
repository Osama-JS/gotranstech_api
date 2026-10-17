<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Go-Tech API Gateway Root & Discovery Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'status'        => 'operational',
        'service'       => 'Go-Tech Enterprise Logistics API Gateway',
        'version'       => 'v1',
        'documentation' => 'https://gotranstech.com',
        'timestamp'     => now()->toIso8601String(),
    ], 200, [
        'Content-Type' => 'application/json; charset=utf-8',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

// Any undefined route returns a clean, secure JSON 404 instead of a Laravel HTML error
Route::fallback(function () {
    return response()->json([
        'status'  => 'error',
        'message' => 'Resource not found or endpoint does not exist.'
    ], 404, [
        'Content-Type' => 'application/json; charset=utf-8',
    ]);
});
