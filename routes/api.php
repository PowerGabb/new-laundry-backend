<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes
Route::get('/branches/nearby', [BranchController::class, 'nearby']);
Route::post('/branches/courier-rates', [BranchController::class, 'courierRates']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('branches', BranchController::class);
});
