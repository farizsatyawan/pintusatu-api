<?php

use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Public Routes (Bisa diakses tanpa login/token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/trial', [AuthController::class, 'registerTrial']);
Route::post('/register/paid', [AuthController::class, 'registerPaid']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/packages', [SubscriptionController::class, 'packages']);
Route::post('/midtrans/notification', [MidtransController::class, 'notification']);

// Master Room Types (CRUD)
Route::apiResource('room-types', RoomTypeController::class);

// Manajemen Unit Kamar Kost
Route::prefix('rooms')->group(function () {
    Route::get('/', [RoomController::class, 'index']);
    Route::post('/', [RoomController::class, 'store']);
    Route::get('/detail', [RoomController::class, 'show']);
    Route::put('/', [RoomController::class, 'update']);
    Route::delete('/', [RoomController::class, 'destroy']);
    Route::post('/assign-tenant', [RoomController::class, 'assignTenant']);
    Route::post('/checkout', [RoomController::class, 'checkoutTenant']);

    // Route dengan path parameter /{id}
    Route::get('/{id}', [RoomController::class, 'show']);
    Route::put('/{id}', [RoomController::class, 'update']);
    Route::delete('/{id}', [RoomController::class, 'destroy']);
    Route::post('/{id}/assign-tenant', [RoomController::class, 'assignTenant']);
    Route::post('/{id}/checkout', [RoomController::class, 'checkoutTenant']);
});

// Protected Routes (Memerlukan Token JWT)
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Manajemen Subscription & Create / Update Paket
    Route::post('/packages', [SubscriptionController::class, 'store']);
    Route::put('/packages/{id}', [SubscriptionController::class, 'update']);
    Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);
});
