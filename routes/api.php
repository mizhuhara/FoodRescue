<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FoodController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PartnerFoodController;
use App\Http\Controllers\Api\V1\PartnerOrderController;
use App\Http\Controllers\Api\V1\PartnerProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth - Public
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Public discovery
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/foods', [FoodController::class, 'index']);
    Route::get('/foods/{food}', [FoodController::class, 'show']);

    // Auth - Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Customer orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    });

    // Partner profile & dashboard
    Route::middleware(['auth:sanctum', 'role:PARTNER'])->prefix('partner')->group(function () {
        Route::get('/profile', [PartnerProfileController::class, 'show']);
        Route::post('/profile', [PartnerProfileController::class, 'store']);
        Route::get('/dashboard', [PartnerProfileController::class, 'dashboard']);

        Route::get('/foods', [PartnerFoodController::class, 'index']);
        Route::post('/foods', [PartnerFoodController::class, 'store']);
        Route::put('/foods/{food}', [PartnerFoodController::class, 'update']);
        Route::delete('/foods/{food}', [PartnerFoodController::class, 'destroy']);

        Route::get('/orders', [PartnerOrderController::class, 'index']);
        Route::patch('/orders/{order}/status', [PartnerOrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/pickup', [PartnerOrderController::class, 'verifyPickup']);
    });
});
