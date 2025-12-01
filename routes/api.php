<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentHourApiController;
use App\Http\Controllers\Api\AgentTransactionApiController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\UserBankAccountController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransferEventController;
use App\Http\Controllers\TransferFeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserVerificationController;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are automatically prefixed with /api by Laravel (see
| bootstrap/app.php). Do NOT add an extra "api" prefix here.
|
*/

// ---------------------------------------------------------------------------
// Public routes (no auth required)
// ---------------------------------------------------------------------------

Route::prefix('currencies')->group(function () {
    Route::get('/', [CurrencyController::class, 'index']);
    Route::get('/{code}', [CurrencyController::class, 'show']);
});

Route::prefix('exchange-rates')->group(function () {
    Route::get('/', [ExchangeRateController::class, 'index']);
    Route::get('/convert', [ExchangeRateController::class, 'convert']);
    Route::get('/{from}/{to}', [ExchangeRateController::class, 'show']);
});

// ---------------------------------------------------------------------------
// Agent public / protected API (from teammates)
// ---------------------------------------------------------------------------

// Public agent hours (no auth)
Route::middleware(['api', 'throttle:api'])->get(
    '/agents/{agent}/hours',
    [AgentHourApiController::class, 'show']
)->name('api.agents.hours.show');

// Protected agent APIs (session auth)
Route::middleware(['web', 'auth', 'throttle:api'])->group(function () {
    Route::put('/agents/{agent}/hours', [AgentHourApiController::class, 'update'])
        ->name('api.agents.hours.update');

    Route::get('/agents/{agent}/transactions', [AgentTransactionApiController::class, 'index'])
        ->name('api.agents.transactions.index');

    Route::post('/agents/{agent}/transactions/process', [AgentTransactionApiController::class, 'process'])
        ->name('api.agents.transactions.process');
});

// ---------------------------------------------------------------------------
// Authenticated user APIs for Person 3 domain
// NOTE: Using 'auth' (session) to avoid Sanctum guard issues. Can be
//       switched to 'auth:sanctum' once your team finalizes token auth.
// ---------------------------------------------------------------------------

Route::middleware(['auth', 'throttle:api'])->group(function () {
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
        Route::get('/summary', [TransferController::class, 'summary']); // preview
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

    // Transfer Fees
    Route::prefix('transfer-fees')->group(function () {
        Route::get('/', [TransferFeeController::class, 'index']);
        Route::post('/calculate', [TransferFeeController::class, 'calculate']);
        Route::get('/{id}', [TransferFeeController::class, 'show']);
        Route::post('/', [TransferFeeController::class, 'store']);   // Admin usage
        Route::put('/{id}', [TransferFeeController::class, 'update']); // Admin usage
        Route::delete('/{id}', [TransferFeeController::class, 'destroy']); // Admin usage
    });
});


// Social Login
Route::post('/auth/social', [AuthController::class, 'socialLogin']);


// PUBLIC: auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);
Route::post('/auth/social',   [AuthController::class, 'socialLogin']);

// PROTECTED: need login
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/me',  [UserController::class, 'me']);
    Route::put('/me',  [UserController::class, 'update']);

    // KYC (any logged-in user can submit their own KYC)
    Route::post('/kyc', [UserVerificationController::class, 'store']);
    Route::get('/kyc',  [UserVerificationController::class, 'show']);

    // Bank accounts require verified KYC
    Route::middleware('kyc_verified')->group(function () {
        Route::get('/bank-accounts',        [UserBankAccountController::class, 'index']);
        Route::post('/bank-accounts',       [UserBankAccountController::class, 'store']);
        Route::get('/bank-accounts/{id}',   [UserBankAccountController::class, 'show']);
        Route::put('/bank-accounts/{id}',   [UserBankAccountController::class, 'update']);
        Route::delete('/bank-accounts/{id}',[UserBankAccountController::class, 'destroy']);
    });
});
