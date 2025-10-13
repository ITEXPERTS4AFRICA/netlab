<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Api\LabController;

Route::middleware('auth')->group(function(){
    Route::get('/reservations/active/{lab_id}', [ReservationController::class, 'active']);

    // Lab reservation endpoints
    Route::post('/api/labs/{lab}/reserve', [ReservationController::class, 'store']);
    Route::post('/api/labs/{lab}/start', [\App\Http\Controllers\LabRuntimeController::class, 'start']);
    Route::post('/api/labs/{lab}/stop', [\App\Http\Controllers\LabRuntimeController::class, 'stop']);
});
