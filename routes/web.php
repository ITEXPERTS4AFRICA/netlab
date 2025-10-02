<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('labs',[\App\Http\Controllers\LabsController::class,'index' ])->name('labs');
    Route::resource('reservations', \App\Http\Controllers\ReservationController::class)->except(['edit', 'update']);
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
