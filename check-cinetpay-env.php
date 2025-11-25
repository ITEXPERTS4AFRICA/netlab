<?php

/**
 * Script pour vérifier la configuration CinetPay dans .env
 * Usage: php check-cinetpay-env.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Vérification de la configuration CinetPay\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Vérifier les variables .env
echo "1. Variables .env:\n";
$envVars = [
    'CINETPAY_API_KEY' => env('CINETPAY_API_KEY'),
    'CINETPAY_SITE_ID' => env('CINETPAY_SITE_ID'),
    'CINETPAY_MODE' => env('CINETPAY_MODE'),
    'CINETPAY_API_URL' => env('CINETPAY_API_URL'),
    'CINETPAY_NOTIFY_URL' => env('CINETPAY_NOTIFY_URL'),
    'CINETPAY_RETURN_URL' => env('CINETPAY_RETURN_URL'),
    'CINETPAY_CANCEL_URL' => env('CINETPAY_CANCEL_URL'),
];

$hasErrors = false;

foreach ($envVars as $key => $value) {
    if (in_array($key, ['CINETPAY_API_KEY', 'CINETPAY_SITE_ID', 'CINETPAY_MODE'])) {
        if (empty($value)) {
            echo "   ❌ {$key}: NON DÉFINI\n";
            $hasErrors = true;
        } else {
            if ($key === 'CINETPAY_API_KEY') {
                echo "   ✅ {$key}: " . substr($value, 0, 10) . "... (longueur: " . strlen($value) . ")\n";
            } else {
                echo "   ✅ {$key}: {$value}\n";
            }
        }
    } else {
        if (empty($value)) {
            echo "   ⚠️  {$key}: Non défini (sera généré automatiquement)\n";
        } else {
            echo "   ✅ {$key}: {$value}\n";
        }
    }
}

// 2. Vérifier la configuration via config()
echo "\n2. Configuration via config():\n";
$config = config('services.cinetpay');
echo "   API Key: " . (!empty($config['api_key']) ? substr($config['api_key'], 0, 10) . '...' : '❌ NON DÉFINI') . "\n";
echo "   Site ID: " . ($config['site_id'] ?? '❌ NON DÉFINI') . "\n";
echo "   Mode: " . ($config['mode'] ?? '❌ NON DÉFINI') . "\n";
echo "   API URL: " . ($config['api_url'] ?? '❌ NON DÉFINI') . "\n";

// 3. Vérifier l'URL finale
echo "\n3. URL finale de l'API:\n";
$apiUrl = $config['api_url'] ?? 'https://api-checkout.cinetpay.com';
$apiUrl = rtrim($apiUrl, '/');
$finalUrl = $apiUrl . '/v2/payment';
echo "   Base URL: {$apiUrl}\n";
echo "   URL complète: {$finalUrl}\n";

if (strpos($apiUrl, '/v2/payment') !== false) {
    echo "   ⚠️  ATTENTION: L'URL de base contient déjà '/v2/payment'\n";
    echo "   💡 CINETPAY_API_URL ne doit contenir que la base: https://api-checkout.cinetpay.com\n";
    $hasErrors = true;
}

if ($apiUrl !== 'https://api-checkout.cinetpay.com' && $apiUrl !== 'https://api.sandbox.cinetpay.com') {
    echo "   ⚠️  URL non standard détectée\n";
}

// 4. Vérifier via CinetPayService
echo "\n4. Configuration via CinetPayService:\n";
try {
    $service = new \App\Services\CinetPayService();
    $reflection = new \ReflectionClass($service);
    
    $apiKey = $reflection->getProperty('apiKey');
    $apiKey->setAccessible(true);
    $apiKeyValue = $apiKey->getValue($service);
    
    $siteId = $reflection->getProperty('siteId');
    $siteId->setAccessible(true);
    $siteIdValue = $siteId->getValue($service);
    
    $mode = $reflection->getProperty('mode');
    $mode->setAccessible(true);
    $modeValue = $mode->getValue($service);
    
    $apiUrlProp = $reflection->getProperty('apiUrl');
    $apiUrlProp->setAccessible(true);
    $apiUrlValue = $apiUrlProp->getValue($service);
    
    echo "   API Key: " . (!empty($apiKeyValue) ? substr($apiKeyValue, 0, 10) . '...' : '❌ NON DÉFINI') . "\n";
    echo "   Site ID: " . ($siteIdValue ?: '❌ NON DÉFINI') . "\n";
    echo "   Mode: " . ($modeValue ?: '❌ NON DÉFINI') . "\n";
    echo "   API URL: {$apiUrlValue}\n";
    
    if ($apiUrlValue !== 'https://api-checkout.cinetpay.com' && $modeValue === 'production') {
        echo "   ⚠️  ATTENTION: En mode production, l'URL devrait être https://api-checkout.cinetpay.com\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    $hasErrors = true;
}

// 5. Recommandations
echo "\n5. Recommandations:\n";
if ($hasErrors) {
    echo "   ❌ Des erreurs ont été détectées. Vérifiez votre fichier .env\n";
    echo "\n   Exemple de configuration correcte dans .env:\n";
    echo "   CINETPAY_API_KEY=votre_api_key\n";
    echo "   CINETPAY_SITE_ID=votre_site_id\n";
    echo "   CINETPAY_MODE=production\n";
    echo "   CINETPAY_API_URL=https://api-checkout.cinetpay.com\n";
    echo "\n   Après modification, exécutez:\n";
    echo "   php artisan config:clear\n";
    exit(1);
} else {
    echo "   ✅ Configuration semble correcte !\n";
    echo "\n   Pour tester la connexion:\n";
    echo "   php artisan cinetpay:diagnose-production\n";
    exit(0);
}

