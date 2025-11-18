<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Ignorer les notices "Broken pipe" du serveur PHP intégré
if (php_sapi_name() === 'cli-server') {
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        // Ignorer uniquement les erreurs "Broken pipe" de file_put_contents
        if ($errno === E_NOTICE && strpos($errstr, 'Broken pipe') !== false && strpos($errfile, 'server.php') !== false) {
            return true; // Ignorer cette erreur
        }
        return false; // Laisser PHP gérer les autres erreurs
    }, E_NOTICE);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
