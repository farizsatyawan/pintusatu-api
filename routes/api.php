<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Public Routes (Bisa diakses tanpa login/token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/trial', [AuthController::class, 'registerTrial']);
Route::post('/register/paid', [AuthController::class, 'registerPaid']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/packages', [SubscriptionController::class, 'packages']);

// Protected Routes (Memerlukan Token JWT)
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Manajemen Subscription & Create Paket
    Route::post('/packages', [SubscriptionController::class, 'store']);
    Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);
});
