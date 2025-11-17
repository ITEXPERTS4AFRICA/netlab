<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CmlConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.users.index');
    })->name('dashboard');

    // Gestion des utilisateurs
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    // Configuration CML
    Route::get('cml-config', [CmlConfigController::class, 'index'])->name('cml-config.index');
    Route::put('cml-config', [CmlConfigController::class, 'update'])->name('cml-config.update');
    Route::post('cml-config/test', [CmlConfigController::class, 'testConnection'])->name('cml-config.test');

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
});

