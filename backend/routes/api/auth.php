<?php

    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\CustomerController;

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::get('/update-email', [CustomerController::class, 'verifyEmail']);
