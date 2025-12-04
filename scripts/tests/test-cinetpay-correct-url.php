<?php

/**
 * Test avec la bonne URL pour confirmer que ça fonctionne
 * Usage: php test-cinetpay-correct-url.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

echo "🧪 Test avec la bonne URL CinetPay\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Configuration
$apiKey = Setting::get('cinetpay.api_key', env('CINETPAY_API_KEY', ''));
$siteId = Setting::get('cinetpay.site_id', env('CINETPAY_SITE_ID', ''));
$mode = Setting::get('cinetpay.mode', env('CINETPAY_MODE', 'sandbox'));

if (empty($apiKey) || empty($siteId)) {
    echo "❌ Configuration incomplète !\n";
    exit(1);
}

echo "1. Configuration:\n";
echo "   API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "   Site ID: {$siteId}\n";
echo "   Mode: {$mode}\n\n";

// 2. Tester les deux URLs
$urls = [
    'INCORRECTE (actuelle)' => 'https://api-checkout.cinetpay.com',
    'CORRECTE (à utiliser)' => 'https://api-checkout.cinetpay.com',
];

foreach ($urls as $label => $baseUrl) {
    echo "2. Test avec URL: {$label}\n";
    echo "   URL: {$baseUrl}\n";
    
    // Test de connectivité
    echo "   Test de connectivité...\n";
    $startTime = microtime(true);
    try {
        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->withoutVerifying()
            ->get($baseUrl);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        echo "   ✅ Accessible ({$duration}ms, Status: {$response->status()})\n";
        
        // Test de l'endpoint de signature
        echo "   Test de l'endpoint de signature...\n";
        $signatureUrl = "{$baseUrl}/v2/?method=getSignatureByPost";
        
        $testData = [
            'apikey' => $apiKey,
            'cpm_site_id' => $siteId,
            'cpm_amount' => 100,
            'cpm_currency' => 'XOF',
            'cpm_trans_id' => 'TEST_' . time(),
        ];
        
        $startTime = microtime(true);
        $response = Http::timeout(15)
            ->connectTimeout(5)
            ->withoutVerifying()
            ->asForm()
            ->post($signatureUrl, $testData);
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $status = $response->status();
        $body = $response->body();
        
        if ($status === 200 && strlen($body) > 20) {
            echo "   ✅ Endpoint fonctionne ! ({$duration}ms)\n";
            echo "   Signature reçue: " . substr($body, 0, 50) . "...\n";
        } else {
            echo "   ⚠️  Réponse inattendue (Status: {$status}, Durée: {$duration}ms)\n";
        }
        
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        echo "   ❌ TIMEOUT après {$duration}ms\n";
        echo "   Erreur: " . $e->getMessage() . "\n";
    } catch (\Exception $e) {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        echo "   ❌ Erreur après {$duration}ms: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// 3. Recommandation
echo str_repeat("=", 60) . "\n";
echo "📋 RECOMMANDATION\n";
echo str_repeat("=", 60) . "\n";
echo "\n";
echo "Modifiez votre fichier .env pour utiliser la bonne URL:\n";
echo "\n";
echo "❌ ACTUELLEMENT:\n";
echo "   CINETPAY_API_URL=https://api-checkout.cinetpay.com\n";
echo "\n";
echo "✅ DOIT ÊTRE:\n";
echo "   CINETPAY_API_URL=https://api-checkout.cinetpay.com\n";
echo "\n";
echo "Puis exécutez:\n";
echo "   php artisan config:clear\n";
echo "   php test-cinetpay-real.php\n";

