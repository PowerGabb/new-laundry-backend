<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes
Route::get('/branches/nearby', [BranchController::class, 'nearby']);
Route::get('/branches/all', [BranchController::class, 'all']);
Route::post('/branches/courier-rates', [BranchController::class, 'courierRates']);
Route::get('/branches/available-couriers', [BranchController::class, 'getAvailableCouriers']);
Route::post('/orders/track', [BranchController::class, 'trackOrder']);

// Midtrans notification webhook (public, no auth)
Route::post('/payments/notification', [PaymentController::class, 'notification']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('branches', BranchController::class);
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::post('/orders/{order}/choose-delivery-payment', [OrderController::class, 'chooseDeliveryAndPayment']);

    // Payment routes
    Route::post('/payments/pay/{order}', [PaymentController::class, 'pay']);
    Route::get('/payments/status/{order}', [PaymentController::class, 'status']);
});
