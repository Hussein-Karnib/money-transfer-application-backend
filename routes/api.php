<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\UserBankAccountController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransferEventController;
use App\Http\Controllers\TransferFeeController;


// Public routes
Route::prefix('currencies')->group(function () {
    Route::get('/', [CurrencyController::class, 'index']);
    Route::get('/{code}', [CurrencyController::class, 'show']);
});

Route::prefix('exchange-rates')->group(function () {
    Route::get('/', [ExchangeRateController::class, 'index']);
    Route::get('/convert', [ExchangeRateController::class, 'convert']);
    Route::get('/{from}/{to}', [ExchangeRateController::class, 'show']);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Bank Accounts
    Route::prefix('bank-accounts')->group(function () {
        Route::get('/', [UserBankAccountController::class, 'index']);
        Route::post('/', [UserBankAccountController::class, 'store']);
        Route::get('/{id}', [UserBankAccountController::class, 'show']);
        Route::put('/{id}', [UserBankAccountController::class, 'update']);
        Route::delete('/{id}', [UserBankAccountController::class, 'destroy']);
        Route::post('/{id}/verify', [UserBankAccountController::class, 'verify']);
    });

    // Beneficiaries
    Route::prefix('beneficiaries')->group(function () {
        Route::get('/', [BeneficiaryController::class, 'index']);
        Route::post('/', [BeneficiaryController::class, 'store']);
        Route::get('/{id}', [BeneficiaryController::class, 'show']);
        Route::put('/{id}', [BeneficiaryController::class, 'update']);
        Route::delete('/{id}', [BeneficiaryController::class, 'destroy']);
    });

    // Transfers
    Route::prefix('transfers')->group(function () {
        Route::get('/', [TransferController::class, 'index']);
        Route::get('/summary', [TransferController::class, 'summary']); // Get transfer summary before creating
        Route::post('/', [TransferController::class, 'store']);
        Route::get('/{id}', [TransferController::class, 'show']);
        Route::get('/{id}/track', [TransferController::class, 'track']);
        Route::post('/{id}/cancel', [TransferController::class, 'cancel']);
        Route::post('/{id}/refund', [TransferController::class, 'refund']);
        Route::get('/{id}/events', [TransferEventController::class, 'index']);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::post('/{id}/capture', [PaymentController::class, 'capture']);
        Route::post('/{id}/refund', [PaymentController::class, 'refund']);
    });

    // Transfer Fees (Admin only - add middleware later)
    Route::prefix('transfer-fees')->group(function () {
        Route::get('/', [TransferFeeController::class, 'index']);
        Route::post('/calculate', [TransferFeeController::class, 'calculate']); // Public endpoint to calculate fee
        Route::get('/{id}', [TransferFeeController::class, 'show']);
        Route::post('/', [TransferFeeController::class, 'store']); // Admin only
        Route::put('/{id}', [TransferFeeController::class, 'update']); // Admin only
        Route::delete('/{id}', [TransferFeeController::class, 'destroy']); // Admin only
    });
});

