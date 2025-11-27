<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\LabDetailsController;
use App\Http\Controllers\Api\LabEventsController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\LabConfigController;
use App\Http\Controllers\Api\ConsoleController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\IntelligentCommandController;
use App\Http\Controllers\ReservationController;

Route::middleware(['web', 'auth'])->group(function(){
    Route::get('/reservations/active/{lab_id}', [ReservationController::class, 'active']);

    // Lab information & topology
    Route::get('/labs', [LabController::class, 'index']);
    
    // Routes spécifiques AVANT les routes génériques {lab}
    // Lab details (complet avec toutes les informations)
    Route::get('/labs/{labId}/details', [LabDetailsController::class, 'show']);
    Route::get('/labs/{labId}/simulation-stats', [LabDetailsController::class, 'simulationStats']);
    Route::get('/labs/{labId}/layer3-addresses', [LabDetailsController::class, 'layer3Addresses']);

    // Lab events and logs
    Route::get('/labs/{labId}/events', [LabEventsController::class, 'index']);
    Route::get('/labs/{labId}/nodes/{nodeId}/events', [LabEventsController::class, 'nodeEvents']);
    Route::get('/labs/{labId}/interfaces/{interfaceId}/events', [LabEventsController::class, 'interfaceEvents']);

    // Lab configuration management (complète)
    Route::get('/labs/{labId}/config', [LabConfigController::class, 'getLabConfig']);
    Route::put('/labs/{labId}/config', [LabConfigController::class, 'updateLabConfig']);
    
    // Routes génériques {lab}
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

    // Console management
    Route::get('/console/ping', [ConsoleController::class, 'ping']);
    Route::get('/labs/{labId}/nodes/{nodeId}/consoles', [ConsoleController::class, 'index']);
    Route::get('/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/log', [ConsoleController::class, 'log']);
    
    // Polling intelligent des logs console
    Route::get('/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/poll', [ConsoleController::class, 'pollLogs']);
    Route::delete('/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/cache', [ConsoleController::class, 'clearLogsCache']);
    
    Route::get('/console/sessions', [ConsoleController::class, 'sessions']);
    Route::post('/console/sessions', [ConsoleController::class, 'store']);
    Route::delete('/console/sessions/{sessionId}', [ConsoleController::class, 'destroy']);

    // Node interfaces and links
    Route::get('/labs/{labId}/nodes/{nodeId}/interfaces', [NodeController::class, 'interfaces']);
    Route::get('/labs/{labId}/links', [NodeController::class, 'links']);
    Route::put('/labs/{labId}/interfaces/{interfaceId}/connect', [NodeController::class, 'connectInterface']);
    Route::put('/labs/{labId}/interfaces/{interfaceId}/disconnect', [NodeController::class, 'disconnectInterface']);
    Route::put('/labs/{labId}/links/{linkId}/connect', [NodeController::class, 'connectLink']);
    Route::put('/labs/{labId}/links/{linkId}/disconnect', [NodeController::class, 'disconnectLink']);

    // Intelligent command generation based on lab structure
    Route::get('/labs/{labId}/commands/analyze', [IntelligentCommandController::class, 'analyzeLab']);
    Route::get('/labs/{labId}/commands/script', [IntelligentCommandController::class, 'generateScript']);
    Route::get('/labs/{labId}/nodes/{nodeId}/commands/recommended', [IntelligentCommandController::class, 'getRecommendedCommands']);
    Route::post('/labs/{labId}/nodes/{nodeId}/commands/execute', [IntelligentCommandController::class, 'executeGeneratedCommand']);

    // Payment management
    Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index']);
    Route::get('/payments/{payment}', [\App\Http\Controllers\PaymentController::class, 'show']);
    Route::post('/reservations/{reservation}/payments/initiate', [\App\Http\Controllers\PaymentController::class, 'initiate']);
    Route::get('/payments/{payment}/check-status', [\App\Http\Controllers\PaymentController::class, 'checkStatus']);

    // Configuration management (upload/export)
    Route::get('/labs/{labId}/nodes/{nodeId}/config', [ConfigController::class, 'getNodeConfig']);
    Route::post('/labs/{labId}/nodes/{nodeId}/config/upload', [ConfigController::class, 'uploadNodeConfig']);
    Route::put('/labs/{labId}/nodes/{nodeId}/config/extract', [ConfigController::class, 'extractNodeConfig']);
    Route::get('/labs/{labId}/nodes/{nodeId}/config/export', [ConfigController::class, 'exportNodeConfig']);
    Route::get('/labs/{labId}/export', [ConfigController::class, 'exportLab']);

    // CML Token management
    Route::post('/cml/token/refresh', [\App\Http\Controllers\Api\CmlTokenController::class, 'refresh']);
    Route::get('/cml/token/check', [\App\Http\Controllers\Api\CmlTokenController::class, 'check']);
});

// Webhooks (sans authentification)
Route::post('/payments/cinetpay/webhook', [\App\Http\Controllers\PaymentController::class, 'webhook'])->name('payments.cinetpay.webhook');
Route::get('/payments/return', [\App\Http\Controllers\PaymentController::class, 'return'])->name('payments.return');
Route::get('/payments/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])->name('payments.cancel');
