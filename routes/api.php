<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserVerificationController;
use App\Http\Controllers\UserBankAccountController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});



Route::middleware(['auth'])->group(function () {

    Route::get('/me', [UserController::class, 'me']);
    Route::put('/me', [UserController::class, 'update']);

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('kyc')->group(function () {
        Route::post('/', [UserVerificationController::class, 'store']);
        Route::get('/',  [UserVerificationController::class, 'show']);
    });

    Route::prefix('bank-accounts')->group(function () {
        Route::get('/',          [UserBankAccountController::class, 'index']);
        Route::post('/',         [UserBankAccountController::class, 'store']);
        Route::get('/{id}',      [UserBankAccountController::class, 'show']);
        Route::put('/{id}',      [UserBankAccountController::class, 'update']);
        Route::delete('/{id}',   [UserBankAccountController::class, 'destroy']);
    });

});
