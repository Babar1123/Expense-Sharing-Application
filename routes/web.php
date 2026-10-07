<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Splitwise API is running.',
        'data' => ['version' => '1.0.0'],
    ]);
});
