<?php

use App\Http\Controllers\Api\AgentHourApiController;
use App\Http\Controllers\Api\AgentTransactionApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::middleware(['api', 'throttle:api'])->get('/agents/{agent}/hours', [AgentHourApiController::class, 'show'])
        ->name('api.agents.hours.show');

    Route::middleware(['web', 'auth', 'throttle:api'])->put('/agents/{agent}/hours', [AgentHourApiController::class, 'update'])
        ->name('api.agents.hours.update');

    Route::middleware(['web', 'auth', 'throttle:api'])->group(function () {
        Route::get('/agents/{agent}/transactions', [AgentTransactionApiController::class, 'index'])
            ->name('api.agents.transactions.index');

        Route::post('/agents/{agent}/transactions/process', [AgentTransactionApiController::class, 'process'])
            ->name('api.agents.transactions.process');
    });
});

