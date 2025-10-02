<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Api\LabController;

Route::middleware('auth')->group(function(){
    Route::get('/reservations', [ReservationController::class,'index']);
    Route::post('/labs/{lab}/reserve', [ReservationController::class,'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class,'show']);
    Route::delete('/reservations/{reservation}', [ReservationController::class,'destroy']);

    // Labs API
    Route::get('/labs', [LabController::class,'index']);
    Route::get('/labs/{lab}', [LabController::class,'show']);
    Route::post('/labs/{lab}/start', [\App\Http\Controllers\Api\LabRuntimeController::class,'start']);
    Route::post('/labs/{lab}/stop', [\App\Http\Controllers\Api\LabRuntimeController::class,'stop']);
    Route::post('/labs/{lab}/wipe', [\App\Http\Controllers\Api\LabRuntimeController::class,'wipe']);
});


