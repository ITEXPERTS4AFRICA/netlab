<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\ReservationController;

Route::middleware(['web', 'auth'])->group(function(){
    Route::get('/reservations/active/{lab_id}', [ReservationController::class, 'active']);

    // Lab information & topology
    Route::get('/labs', [LabController::class, 'index']);
    Route::get('/labs/{lab}/topology', [LabController::class, 'topology']);
    Route::get('/labs/{lab}/state', [LabController::class, 'state']);
    Route::get('/labs/{lab}/convergence', [LabController::class, 'convergence']);
    Route::get('/labs/{lab}', [LabController::class, 'show']);

    // Lab reservation endpoints
    Route::post('/labs/reserve', [ReservationController::class, 'store']);
    
    // Lab runtime control endpoints
    Route::post('/labs/{lab}/start', [\App\Http\Controllers\LabRuntimeController::class, 'start']);
    Route::post('/labs/{lab}/stop', [\App\Http\Controllers\LabRuntimeController::class, 'stop']);
    Route::post('/labs/{lab}/wipe', [\App\Http\Controllers\LabRuntimeController::class, 'wipe']);
    Route::post('/labs/{lab}/restart', [\App\Http\Controllers\LabRuntimeController::class, 'restart']);
    Route::get('/labs/{lab}/export', [\App\Http\Controllers\LabRuntimeController::class, 'export']);
    Route::get('/labs/{lab}/check', [LabController::class, 'convergence']); // Alias pour convergence

    // Payment management
    Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index']);
    Route::get('/payments/{payment}', [\App\Http\Controllers\PaymentController::class, 'show']);
    Route::post('/reservations/{reservation}/payments/initiate', [\App\Http\Controllers\PaymentController::class, 'initiate']);
    Route::get('/payments/{payment}/check-status', [\App\Http\Controllers\PaymentController::class, 'checkStatus']);

    // Token management
    Route::get('/tokens', [\App\Http\Controllers\TokenController::class, 'index']);
    Route::post('/tokens/buy', [\App\Http\Controllers\TokenController::class, 'buy']);

    // CML Token management
    Route::post('/cml/token/refresh', [\App\Http\Controllers\Api\CmlTokenController::class, 'refresh']);
    Route::get('/cml/token/check', [\App\Http\Controllers\Api\CmlTokenController::class, 'check']);
});

// Webhooks (sans authentification)
Route::post('/payments/cinetpay/webhook', [\App\Http\Controllers\PaymentController::class, 'webhook'])->name('payments.cinetpay.webhook');
Route::get('/payments/return', [\App\Http\Controllers\PaymentController::class, 'return'])->name('payments.return');
Route::get('/payments/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])->name('payments.cancel');
