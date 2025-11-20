<?php

use App\Http\Controllers\Admin\CmlConfigController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentHealthController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Gestion des utilisateurs
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/warn', [UserController::class, 'issueWarning'])->name('users.warn');
    Route::post('users/{user}/ban', [UserController::class, 'banUser'])->name('users.ban');
    Route::post('users/{user}/unban', [UserController::class, 'unbanUser'])->name('users.unban');
    Route::get('users/{user}/warnings', [UserController::class, 'getWarnings'])->name('users.warnings');
    Route::get('users/{user}/ban-history', [UserController::class, 'getBanHistory'])->name('users.ban-history');

    // Configuration CML
    Route::get('cml-config', [CmlConfigController::class, 'index'])->name('cml-config.index');
    Route::put('cml-config', [CmlConfigController::class, 'update'])->name('cml-config.update');
    Route::post('cml-config/test', [CmlConfigController::class, 'testConnection'])->name('cml-config.test');

    // Configuration CinetPay
    Route::get('cinetpay-config', [\App\Http\Controllers\Admin\CinetPayConfigController::class, 'index'])->name('cinetpay-config.index');
    Route::put('cinetpay-config', [\App\Http\Controllers\Admin\CinetPayConfigController::class, 'update'])->name('cinetpay-config.update');
    Route::post('cinetpay-config/test', [\App\Http\Controllers\Admin\CinetPayConfigController::class, 'testConnection'])->name('cinetpay-config.test');

    // Santé de l'API de paiement
    Route::get('payments/health', [PaymentHealthController::class, 'index'])->name('payments.health');
    Route::post('payments/health/refresh', [PaymentHealthController::class, 'refresh'])->name('payments.health.refresh');

    // Gestion des labs (sans création manuelle)
    Route::get('labs', [\App\Http\Controllers\Admin\LabController::class, 'index'])->name('labs.index');
    Route::get('labs/{lab}', [\App\Http\Controllers\Admin\LabController::class, 'show'])->name('labs.show');
    Route::get('labs/{lab}/edit', [\App\Http\Controllers\Admin\LabController::class, 'edit'])->name('labs.edit');
    Route::put('labs/{lab}', [\App\Http\Controllers\Admin\LabController::class, 'update'])->name('labs.update');
    Route::delete('labs/{lab}', [\App\Http\Controllers\Admin\LabController::class, 'destroy'])->name('labs.destroy');
    Route::post('labs/sync-from-cml', [\App\Http\Controllers\Admin\LabController::class, 'syncFromCml'])->name('labs.sync-from-cml');
    Route::patch('labs/{lab}/toggle-featured', [\App\Http\Controllers\Admin\LabController::class, 'toggleFeatured'])->name('labs.toggle-featured');
    Route::patch('labs/{lab}/toggle-published', [\App\Http\Controllers\Admin\LabController::class, 'togglePublished'])->name('labs.toggle-published');
    Route::patch('labs/{lab}/toggle-restricted', [\App\Http\Controllers\Admin\LabController::class, 'toggleRestricted'])->name('labs.toggle-restricted');

    // Gestion des médias de documentation
    Route::post('labs/{lab}/media/upload', [\App\Http\Controllers\Admin\LabController::class, 'uploadMedia'])->name('labs.media.upload');
    Route::post('labs/{lab}/media/link', [\App\Http\Controllers\Admin\LabController::class, 'addLink'])->name('labs.media.link');
    Route::put('labs/{lab}/media/{media}', [\App\Http\Controllers\Admin\LabController::class, 'updateMedia'])->name('labs.media.update');
    Route::delete('labs/{lab}/media/{media}', [\App\Http\Controllers\Admin\LabController::class, 'deleteMedia'])->name('labs.media.delete');
    Route::post('labs/{lab}/media/reorder', [\App\Http\Controllers\Admin\LabController::class, 'reorderMedia'])->name('labs.media.reorder');

    // Gestion des snapshots (sauvegarde/restauration)
    Route::get('labs/{lab}/snapshots', [\App\Http\Controllers\Admin\LabController::class, 'listSnapshots'])->name('labs.snapshots.index');
    Route::post('labs/{lab}/snapshots', [\App\Http\Controllers\Admin\LabController::class, 'saveSnapshot'])->name('labs.snapshots.save');
    Route::post('labs/{lab}/snapshots/{snapshot}/restore', [\App\Http\Controllers\Admin\LabController::class, 'restoreSnapshot'])->name('labs.snapshots.restore');
    Route::patch('labs/{lab}/snapshots/{snapshot}/set-default', [\App\Http\Controllers\Admin\LabController::class, 'setDefaultSnapshot'])->name('labs.snapshots.set-default');
    Route::delete('labs/{lab}/snapshots/{snapshot}', [\App\Http\Controllers\Admin\LabController::class, 'deleteSnapshot'])->name('labs.snapshots.delete');

    // Gestion des réservations
    Route::get('reservations', [\App\Http\Controllers\Admin\ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservations/{reservation}', [\App\Http\Controllers\Admin\ReservationController::class, 'show'])->name('reservations.show');
    Route::put('reservations/{reservation}', [\App\Http\Controllers\Admin\ReservationController::class, 'update'])->name('reservations.update');
    Route::post('reservations/{reservation}/cancel', [\App\Http\Controllers\Admin\ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::post('reservations/cleanup', [\App\Http\Controllers\Admin\ReservationController::class, 'cleanupExpired'])->name('reservations.cleanup');
});

