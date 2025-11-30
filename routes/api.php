<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::post('/api/generate-token', [ApiController::class, 'generateToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/api/capture-payments', [ApiController::class, 'capturePayments']);
    Route::post('/api/capture-damage-loss-late-fee', [ApiController::class, 'captureDamageLossLateFee']);
    Route::post('/api/refund', [ApiController::class, 'refund']);
    Route::post('/api/cancel-refund', [ApiController::class, 'cancelRefund']);
    Route::post('/api/void', [ApiController::class, 'voidOrder']);
    Route::post('/api/partially-paid', [ApiController::class, 'partiallyPaid']);
    Route::post('/api/partially-refund', [ApiController::class, 'partiallyRefund']);
    Route::post('/api/lender-onboarding-link', [ApiController::class, 'lenderOnboardingLink']);
    Route::post('/api/lender-payout', [ApiController::class, 'lenderPayout']);
});

