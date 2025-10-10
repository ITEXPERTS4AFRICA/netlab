<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LabController;


Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('labs',[\App\Http\Controllers\LabsController::class,'index' ])->name('labs');
    Route::get('labs/my-reserved',[\App\Http\Controllers\LabsController::class,'myReservedLabs' ])->name('labs.my-reserved');
    Route::resource('reservations', \App\Http\Controllers\ReservationController::class)->except(['edit', 'update']);
    Route::post('reservations/custom-create', [\App\Http\Controllers\ReservationController::class, 'createReservation'])->name('reservations.custom-create');

    Route::post('/labs/{lab}/reserve', [\App\Http\Controllers\ReservationController::class,'store']);

    // Lab Workspace
    Route::get('/labs/{lab}/workspace', [\App\Http\Controllers\LabsController::class,'workspace'])->name('labs.workspace');

    // Labs
    Route::get('/labs/{lab}/annotations', [\App\Http\Controllers\AnnotationsController::class,'index']);
    Route::post('/labs/{lab}/annotations', [\App\Http\Controllers\AnnotationsController::class,'store']);
    Route::patch('/labs/{lab}/annotations/{annotation}', [\App\Http\Controllers\AnnotationsController::class,'update']);
    Route::delete('/labs/{lab}/annotations/{annotation}', [\App\Http\Controllers\AnnotationsController::class,'destroy']);
    Route::get('/labs/{lab}/schema', [\App\Http\Controllers\AnnotationsController::class,'schema']);

    // Smart Annotations
    Route::get('/labs/{lab}/smart_annotations', [\App\Http\Controllers\SmartAnnotationsController::class,'index']);
    Route::get('/labs/{lab}/smart_annotations/{smart_annotation}', [\App\Http\Controllers\SmartAnnotationsController::class,'show']);
    Route::patch('/labs/{lab}/smart_annotations/{smart_annotation}', [\App\Http\Controllers\SmartAnnotationsController::class,'update']);

    Route::post('/labs/{lab}/start', [\App\Http\Controllers\LabRuntimeController::class,'start']);
    Route::post('/labs/{lab}/stop', [\App\Http\Controllers\LabRuntimeController::class,'stop']);
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
