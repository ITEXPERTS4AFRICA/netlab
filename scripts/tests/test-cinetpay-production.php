<?php

/**
 * Script de test rapide pour CinetPay en production
 * Usage: php test-cinetpay-production.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

echo "🔍 Test CinetPay Production\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Configuration
echo "1. Configuration:\n";
$apiKey = Setting::get('cinetpay.api_key', env('CINETPAY_API_KEY', ''));
$siteId = Setting::get('cinetpay.site_id', env('CINETPAY_SITE_ID', ''));
$mode = Setting::get('cinetpay.mode', env('CINETPAY_MODE', 'sandbox'));

echo "   API Key: " . (empty($apiKey) ? '❌ NON DÉFINI' : substr($apiKey, 0, 10) . '...') . "\n";
echo "   Site ID: " . (empty($siteId) ? '❌ NON DÉFINI' : $siteId) . "\n";
echo "   Mode: " . ($mode ?: '❌ NON DÉFINI') . "\n\n";

if (empty($apiKey) || empty($siteId)) {
    echo "❌ Configuration incomplète !\n";
    exit(1);
}

// 2. Test de connectivité
echo "2. Test de connectivité:\n";
$baseUrl = $mode === 'production' 
    ? 'https://api-checkout.cinetpay.com'
    : 'https://api.sandbox.cinetpay.com';

echo "   URL de base: {$baseUrl}\n";

// Test DNS
echo "   Test DNS...\n";
$host = parse_url($baseUrl, PHP_URL_HOST);
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "   ❌ DNS: Impossible de résoudre {$host}\n";
} else {
    echo "   ✅ DNS: {$host} -> {$ip}\n";
}

// Test HTTP simple
echo "   Test HTTP...\n";
$startTime = microtime(true);
try {
    $response = Http::timeout(10)
        ->connectTimeout(5)
        ->withoutVerifying()
        ->get($baseUrl);
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($response->successful() || $response->status() === 404 || $response->status() === 405) {
        echo "   ✅ HTTP: Accessible ({$duration}ms, Status: {$response->status()})\n";
    } else {
        echo "   ⚠️  HTTP: Status {$response->status()} ({$duration}ms)\n";
    }
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "   ❌ HTTP: TIMEOUT après {$duration}ms\n";
    echo "   Erreur: " . $e->getMessage() . "\n";
    echo "\n💡 Problème détecté: Le serveur ne peut pas se connecter à CinetPay\n";
    echo "   Solutions possibles:\n";
    echo "   1. Vérifier le firewall: sudo ufw status\n";
    echo "   2. Tester manuellement: curl -v {$baseUrl}\n";
    echo "   3. Vérifier les règles de proxy\n";
    exit(1);
} catch (\Exception $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "   ❌ HTTP: Erreur après {$duration}ms\n";
    echo "   Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Test de l'endpoint de signature
echo "\n3. Test de l'endpoint de signature:\n";
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
    
    echo "   ✅ Réponse reçue en {$duration}ms\n";
    echo "   Status: {$response->status()}\n";
    
    $body = $response->body();
    if (strlen($body) > 20) {
        echo "   ✅ Signature probablement valide (longueur: " . strlen($body) . ")\n";
    } else {
        echo "   ⚠️  Réponse courte: " . substr($body, 0, 100) . "\n";
    }
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "   ❌ TIMEOUT après {$duration}ms\n";
    echo "   Erreur: " . $e->getMessage() . "\n";
    echo "\n💡 PROBLÈME IDENTIFIÉ: Timeout de connexion à l'API CinetPay\n";
    echo "\nSolutions:\n";
    echo "1. Vérifier la connectivité réseau du serveur\n";
    echo "2. Vérifier les règles de firewall\n";
    echo "3. Contacter CinetPay pour vérifier l'état de leur API\n";
    echo "4. Augmenter temporairement les timeouts dans CinetPayService.php\n";
    exit(1);
} catch (\Exception $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "   ❌ Erreur après {$duration}ms: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Tous les tests sont passés !\n";

