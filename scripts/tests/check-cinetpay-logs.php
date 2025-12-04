<?php

/**
 * Script pour vérifier les logs CinetPay et identifier le problème
 * Usage: php check-cinetpay-logs.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Log;
use App\Services\CinetPayService;
use App\Models\Setting;

echo "🔍 Analyse des logs et configuration CinetPay\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Vérifier la configuration actuelle
echo "1. Configuration actuelle:\n";
$config = config('services.cinetpay');
echo "   API URL (config): " . ($config['api_url'] ?? 'NON DÉFINI') . "\n";
echo "   API URL (env): " . (env('CINETPAY_API_URL') ?: 'NON DÉFINI') . "\n";
echo "   Mode: " . ($config['mode'] ?? 'NON DÉFINI') . "\n\n";

// 2. Vérifier via CinetPayService
echo "2. Configuration via CinetPayService:\n";
try {
    $service = new CinetPayService();
    $reflection = new \ReflectionClass($service);
    
    $apiUrlProp = $reflection->getProperty('apiUrl');
    $apiUrlProp->setAccessible(true);
    $apiUrlValue = $apiUrlProp->getValue($service);
    
    echo "   API URL (service): {$apiUrlValue}\n";
    
    // Simuler la construction de l'URL comme dans initiatePayment
    $baseUrl = config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com');
    $cleanedBaseUrl = rtrim(str_replace('/v2/payment', '', $baseUrl), '/');
    $finalUrl = $cleanedBaseUrl . '/v2/payment';
    
    echo "   URL finale construite: {$finalUrl}\n\n";
    
    if ($finalUrl !== 'https://api-checkout.cinetpay.com/v2/payment') {
        echo "   ⚠️  PROBLÈME: L'URL finale est incorrecte !\n";
        echo "   Attendu: https://api-checkout.cinetpay.com/v2/payment\n";
        echo "   Obtenu: {$finalUrl}\n\n";
    } else {
        echo "   ✅ URL finale correcte\n\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n\n";
}

// 3. Lire les logs récents
echo "3. Analyse des logs récents:\n";
$logFile = storage_path('logs/laravel.log');

if (!file_exists($logFile)) {
    echo "   ⚠️  Fichier de log non trouvé: {$logFile}\n\n";
} else {
    // Lire les 200 dernières lignes
    $lines = file($logFile);
    $recentLines = array_slice($lines, -200);
    
    $cinetpayLogs = [];
    foreach ($recentLines as $line) {
        if (stripos($line, 'cinetpay') !== false || stripos($line, 'CinetPay') !== false) {
            $cinetpayLogs[] = $line;
        }
    }
    
    if (empty($cinetpayLogs)) {
        echo "   ℹ️  Aucun log CinetPay récent trouvé\n\n";
    } else {
        echo "   📋 " . count($cinetpayLogs) . " log(s) CinetPay trouvé(s)\n\n";
        
        // Chercher les logs d'URL
        $urlLogs = [];
        $errorLogs = [];
        
        foreach ($cinetpayLogs as $log) {
            if (stripos($log, 'URL') !== false || stripos($log, 'url') !== false) {
                $urlLogs[] = $log;
            }
            if (stripos($log, 'error') !== false || stripos($log, 'ERROR') !== false) {
                $errorLogs[] = $log;
            }
        }
        
        if (!empty($urlLogs)) {
            echo "   📍 Logs d'URL (derniers):\n";
            foreach (array_slice($urlLogs, -5) as $log) {
                echo "      " . trim($log) . "\n";
            }
            echo "\n";
        }
        
        if (!empty($errorLogs)) {
            echo "   ❌ Logs d'erreur (derniers):\n";
            foreach (array_slice($errorLogs, -5) as $log) {
                echo "      " . trim($log) . "\n";
            }
            echo "\n";
        }
    }
}

// 4. Test de l'URL actuelle
echo "4. Test de l'URL actuelle:\n";
$baseUrl = config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com');
$cleanedBaseUrl = rtrim(str_replace('/v2/payment', '', $baseUrl), '/');
$testUrl = $cleanedBaseUrl . '/v2/payment';

echo "   URL à tester: {$testUrl}\n";

$apiKey = Setting::get('cinetpay.api_key', env('CINETPAY_API_KEY', ''));
$siteId = Setting::get('cinetpay.site_id', env('CINETPAY_SITE_ID', ''));

if (empty($apiKey) || empty($siteId)) {
    echo "   ⚠️  Configuration incomplète, test impossible\n";
} else {
    echo "   Test en cours...\n";
    
    $testPayload = [
        'apikey' => $apiKey,
        'site_id' => $siteId,
        'transaction_id' => 'TEST_' . time(),
        'amount' => 100,
        'currency' => 'XOF',
        'description' => 'Test',
    ];
    
    try {
        $response = \Illuminate\Support\Facades\Http::asJson()
            ->timeout(15)
            ->connectTimeout(5)
            ->withoutVerifying()
            ->post($testUrl, $testPayload);
        
        $status = $response->status();
        $body = $response->body();
        
        echo "   Status: {$status}\n";
        
        if (stripos($body, '<!DOCTYPE') !== false || stripos($body, '<html') !== false) {
            echo "   ❌ L'API retourne du HTML (404) - URL incorrecte !\n";
            echo "   💡 Vérifiez que CINETPAY_API_URL=https://api-checkout.cinetpay.com dans .env\n";
        } elseif ($status === 200) {
            $data = json_decode($body, true);
            if ($data && isset($data['data']['payment_url'])) {
                echo "   ✅ L'URL fonctionne correctement !\n";
            } else {
                echo "   ⚠️  Réponse reçue mais format inattendu\n";
            }
        } else {
            echo "   ⚠️  Status HTTP: {$status}\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 RECOMMANDATIONS\n";
echo str_repeat("=", 60) . "\n";
echo "\n";
echo "1. Vérifier le fichier .env:\n";
echo "   grep CINETPAY_API_URL .env\n";
echo "   Doit afficher: CINETPAY_API_URL=https://api-checkout.cinetpay.com\n";
echo "\n";
echo "2. Vider le cache de configuration:\n";
echo "   php artisan config:clear\n";
echo "   php artisan cache:clear\n";
echo "\n";
echo "3. Vérifier à nouveau:\n";
echo "   php check-cinetpay-logs.php\n";

