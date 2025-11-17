<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\ConsoleController;
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
    Route::post('/labs/{lab}/start', [\App\Http\Controllers\LabRuntimeController::class, 'start']);
    Route::post('/labs/{lab}/stop', [\App\Http\Controllers\LabRuntimeController::class, 'stop']);

    // Console management
    Route::get('/labs/{labId}/nodes/{nodeId}/consoles', [ConsoleController::class, 'index']);
    Route::get('/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/log', [ConsoleController::class, 'log']);
    Route::get('/console/sessions', [ConsoleController::class, 'sessions']);
    Route::post('/console/sessions', [ConsoleController::class, 'store']);
    Route::delete('/console/sessions/{sessionId}', [ConsoleController::class, 'destroy']);

    // Payment management
    Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index']);
    Route::get('/payments/{payment}', [\App\Http\Controllers\PaymentController::class, 'show']);
    Route::post('/reservations/{reservation}/payments/initiate', [\App\Http\Controllers\PaymentController::class, 'initiate']);
    Route::get('/payments/{payment}/check-status', [\App\Http\Controllers\PaymentController::class, 'checkStatus']);
});

// Webhooks (sans authentification)
Route::post('/payments/cinetpay/webhook', [\App\Http\Controllers\PaymentController::class, 'webhook'])->name('payments.cinetpay.webhook');
Route::get('/payments/return', [\App\Http\Controllers\PaymentController::class, 'return'])->name('payments.return');
Route::get('/payments/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])->name('payments.cancel');
