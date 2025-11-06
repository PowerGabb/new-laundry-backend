<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\LaundryCategoryController;
use App\Http\Controllers\LaundryItemController;
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

// Get categories & items for a branch (public - for mobile app)
Route::get('/branches/{branchId}/categories', [LaundryCategoryController::class, 'forBranch']);

// Banners (public - for mobile app)
Route::get('/banners', [BannerController::class, 'index']);

// Midtrans notification webhook (public, no auth)
Route::post('/payments/notification', [PaymentController::class, 'notification']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return $request->user();
    });
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);

    // Branch stats (must be before apiResource to avoid conflict)
    Route::get('/branches/stats', [OrderController::class, 'getBranchStats']);

    Route::apiResource('branches', BranchController::class);
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::get('/orders/stats', [OrderController::class, 'getStats']);
    Route::post('/orders/{order}/choose-delivery-payment', [OrderController::class, 'chooseDeliveryAndPayment']);
    Route::post('/orders/{order}/update-status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/update-actual-weight', [OrderController::class, 'updateActualWeight']);

    // Banners (admin only)
    Route::get('/banners/all', [BannerController::class, 'all']);
    Route::apiResource('banners', BannerController::class)->except(['index']);

    // Laundry Categories & Items (admin only)
    Route::apiResource('categories', LaundryCategoryController::class);
    Route::apiResource('categories.items', LaundryItemController::class)->shallow();

    // Payment routes
    Route::post('/payments/pay/{order}', [PaymentController::class, 'pay']);
    Route::get('/payments/status/{order}', [PaymentController::class, 'status']);
});
