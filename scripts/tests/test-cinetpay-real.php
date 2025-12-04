<?php

/**
 * Test concret de CinetPay avec une vraie requête
 * Usage: php test-cinetpay-real.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Services\CinetPayService;
use App\Models\Setting;

echo "🧪 Test concret de CinetPay\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Récupérer la configuration
echo "1. Récupération de la configuration...\n";
$apiKey = Setting::get('cinetpay.api_key', env('CINETPAY_API_KEY', ''));
$siteId = Setting::get('cinetpay.site_id', env('CINETPAY_SITE_ID', ''));
$mode = Setting::get('cinetpay.mode', env('CINETPAY_MODE', 'sandbox'));

if (empty($apiKey) || empty($siteId)) {
    echo "❌ Configuration incomplète !\n";
    echo "   API Key: " . (empty($apiKey) ? 'NON DÉFINI' : 'OK') . "\n";
    echo "   Site ID: " . (empty($siteId) ? 'NON DÉFINI' : 'OK') . "\n";
    exit(1);
}

echo "   ✅ API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "   ✅ Site ID: {$siteId}\n";
echo "   ✅ Mode: {$mode}\n\n";

// 2. Déterminer l'URL de base
$baseUrl = $mode === 'production' 
    ? 'https://api-checkout.cinetpay.com'
    : 'https://api.sandbox.cinetpay.com';

// Vérifier si une URL personnalisée est définie
$customUrl = env('CINETPAY_API_URL');
if (!empty($customUrl)) {
    $baseUrl = rtrim($customUrl, '/');
    echo "   ℹ️  URL personnalisée détectée: {$baseUrl}\n";
}

echo "\n2. Test de connectivité réseau...\n";
$startTime = microtime(true);
try {
    $response = Http::timeout(10)
        ->connectTimeout(5)
        ->withoutVerifying()
        ->get($baseUrl);
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($response->successful() || $response->status() === 404 || $response->status() === 405) {
        echo "   ✅ Serveur accessible ({$duration}ms, Status: {$response->status()})\n";
    } else {
        echo "   ⚠️  Serveur répond mais avec un statut inattendu: {$response->status()}\n";
    }
} catch (\Exception $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "   ❌ TIMEOUT ou erreur de connexion ({$duration}ms)\n";
    echo "   Erreur: " . $e->getMessage() . "\n";
    echo "\n💡 Le serveur ne peut pas se connecter à CinetPay. Vérifiez:\n";
    echo "   - Le firewall\n";
    echo "   - La connectivité réseau\n";
    echo "   - Les règles de proxy\n";
    exit(1);
}

// 3. Test de l'endpoint de signature (test simple)
echo "\n3. Test de l'endpoint de signature...\n";
$signatureUrl = "{$baseUrl}/v2/?method=getSignatureByPost";

$testData = [
    'apikey' => $apiKey,
    'cpm_site_id' => $siteId,
    'cpm_amount' => 100,
    'cpm_currency' => 'XOF',
    'cpm_trans_id' => 'TEST_' . time(),
];

echo "   URL: {$signatureUrl}\n";
echo "   Envoi de la requête...\n";

$startTime = microtime(true);
try {
    $response = Http::timeout(15)
        ->connectTimeout(5)
        ->withoutVerifying()
        ->asForm()
        ->post($signatureUrl, $testData);
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $status = $response->status();
    $body = $response->body();
    
    echo "   ✅ Réponse reçue en {$duration}ms\n";
    echo "   Status HTTP: {$status}\n";
    echo "   Longueur de la réponse: " . strlen($body) . " caractères\n";
    
    if ($status === 200) {
        if (strlen($body) > 20 && strlen($body) < 200) {
            echo "   ✅ Signature probablement valide reçue\n";
            echo "   Signature (premiers caractères): " . substr($body, 0, 50) . "...\n";
        } else {
            echo "   ⚠️  Réponse inattendue (longueur: " . strlen($body) . ")\n";
            echo "   Contenu: " . substr($body, 0, 200) . "\n";
        }
    } else {
        echo "   ⚠️  Status HTTP: {$status}\n";
        echo "   Réponse: " . substr($body, 0, 200) . "\n";
    }
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "   ❌ TIMEOUT après {$duration}ms\n";
    echo "   Erreur: " . $e->getMessage() . "\n";
    echo "\n💡 PROBLÈME: L'API CinetPay ne répond pas dans les délais\n";
    echo "   Solutions possibles:\n";
    echo "   1. Vérifier la connectivité réseau du serveur\n";
    echo "   2. Vérifier les règles de firewall\n";
    echo "   3. Contacter CinetPay pour vérifier l'état de l'API\n";
    exit(1);
} catch (\Exception $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "   ❌ Erreur après {$duration}ms: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Test avec CinetPayService (test complet)
echo "\n4. Test avec CinetPayService (test complet)...\n";
try {
    $service = new CinetPayService();
    
    $paymentData = [
        'amount' => 10000, // 100 XOF en centimes
        'currency' => 'XOF',
        'transaction_id' => 'TEST_' . time(),
        'description' => 'Test de paiement',
        'customer_name' => 'Test',
        'customer_surname' => 'User',
        'customer_email' => 'test@example.com',
    ];
    
    echo "   Initialisation d'un paiement test...\n";
    $startTime = microtime(true);
    
    $result = $service->initiatePayment($paymentData);
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "   ✅ Requête terminée en {$duration}ms\n";
    
    if ($result['success'] ?? false) {
        echo "   ✅ SUCCÈS ! Paiement initialisé avec succès\n";
        echo "   Transaction ID: " . ($result['transaction_id'] ?? 'N/A') . "\n";
        if (isset($result['payment_url'])) {
            echo "   Payment URL: " . substr($result['payment_url'], 0, 80) . "...\n";
        }
    } else {
        $error = $result['error'] ?? 'Erreur inconnue';
        $code = $result['code'] ?? 'UNKNOWN';
        $isTimeout = $result['is_timeout'] ?? false;
        
        echo "   ❌ ÉCHEC\n";
        echo "   Code: {$code}\n";
        echo "   Erreur: {$error}\n";
        
        if ($isTimeout) {
            echo "\n   ⚠️  TIMEOUT détecté\n";
            echo "   L'API CinetPay ne répond pas dans les délais impartis.\n";
            echo "   Vérifiez la connectivité réseau du serveur.\n";
        } else {
            echo "\n   ⚠️  Erreur API (pas un timeout)\n";
            echo "   Vérifiez:\n";
            echo "   - Les identifiants (API Key, Site ID)\n";
            echo "   - Le mode (production/sandbox)\n";
            echo "   - Les logs pour plus de détails\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   Trace: " . substr($e->getTraceAsString(), 0, 300) . "...\n";
    exit(1);
}

// 5. Résumé
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ\n";
echo str_repeat("=", 60) . "\n";

if (isset($result) && ($result['success'] ?? false)) {
    echo "✅ TOUS LES TESTS SONT PASSÉS !\n";
    echo "\nCinetPay est correctement configuré et fonctionne.\n";
    exit(0);
} else {
    echo "❌ CERTAINS TESTS ONT ÉCHOUÉ\n";
    echo "\nVérifiez les erreurs ci-dessus et corrigez la configuration.\n";
    exit(1);
}

