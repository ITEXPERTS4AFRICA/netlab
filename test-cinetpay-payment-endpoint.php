<?php

/**
 * Test de l'endpoint de paiement complet
 * Usage: php test-cinetpay-payment-endpoint.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Setting;

echo "🧪 Test de l'endpoint de paiement CinetPay\n";
echo str_repeat("=", 60) . "\n\n";

$apiKey = Setting::get('cinetpay.api_key', env('CINETPAY_API_KEY', ''));
$siteId = Setting::get('cinetpay.site_id', env('CINETPAY_SITE_ID', ''));

if (empty($apiKey) || empty($siteId)) {
    echo "❌ Configuration incomplète !\n";
    exit(1);
}

// Tester avec la bonne URL
$baseUrl = 'https://api-checkout.cinetpay.com';
$paymentUrl = "{$baseUrl}/v2/payment";

echo "1. Test de l'endpoint de paiement complet\n";
echo "   URL: {$paymentUrl}\n\n";

$payload = [
    'apikey' => $apiKey,
    'site_id' => $siteId,
    'transaction_id' => 'TEST_' . time(),
    'amount' => 100, // 100 XOF
    'currency' => 'XOF',
    'description' => 'Test de paiement',
    'notify_url' => url('/api/payments/cinetpay/webhook'),
    'return_url' => url('/api/payments/return'),
    'channels' => 'ALL',
    'customer_name' => 'Test',
    'customer_surname' => 'User',
    'customer_email' => 'test@example.com',
];

echo "2. Envoi de la requête POST (format JSON)...\n";
echo "   Transaction ID: {$payload['transaction_id']}\n";
echo "   Montant: {$payload['amount']} XOF\n\n";

$startTime = microtime(true);
try {
    $response = Http::asJson()
        ->timeout(30)
        ->connectTimeout(10)
        ->withoutVerifying()
        ->post($paymentUrl, $payload);
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $status = $response->status();
    $body = $response->body();
    
    echo "3. Résultat:\n";
    echo "   ✅ Réponse reçue en {$duration}ms\n";
    echo "   Status HTTP: {$status}\n";
    echo "   Longueur de la réponse: " . strlen($body) . " caractères\n\n";
    
    // Essayer de parser la réponse JSON
    $data = json_decode($body, true);
    
    if ($data !== null) {
        echo "4. Réponse JSON:\n";
        echo "   Code: " . ($data['code'] ?? 'N/A') . "\n";
        echo "   Message: " . ($data['message'] ?? 'N/A') . "\n";
        
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                echo "   Data keys: " . implode(', ', array_keys($data['data'])) . "\n";
                if (isset($data['data']['payment_url'])) {
                    echo "   ✅ Payment URL trouvée !\n";
                    echo "   URL: " . substr($data['data']['payment_url'], 0, 80) . "...\n";
                }
            } else {
                echo "   Data: " . substr((string)$data['data'], 0, 100) . "\n";
            }
        }
        
        echo "\n   Réponse complète (premiers 500 caractères):\n";
        echo "   " . substr(json_encode($data, JSON_PRETTY_PRINT), 0, 500) . "...\n";
        
        // Vérifier si c'est un succès
        $code = $data['code'] ?? null;
        $isSuccess = ($code === '0' || $code === '201' || $code === 201 || $code === 'SUCCES');
        $hasPaymentUrl = isset($data['data']['payment_url']) || isset($data['payment_url']);
        
        if ($isSuccess || $hasPaymentUrl) {
            echo "\n✅ SUCCÈS ! L'API CinetPay fonctionne correctement.\n";
            exit(0);
        } else {
            echo "\n⚠️  L'API a répondu mais sans payment_url.\n";
            echo "   Vérifiez les identifiants (API Key, Site ID).\n";
            exit(1);
        }
    } else {
        echo "4. Réponse (non-JSON):\n";
        echo "   " . substr($body, 0, 500) . "\n";
        
        if ($status === 200) {
            echo "\n⚠️  Status 200 mais réponse non-JSON. Vérifiez le format.\n";
        } else {
            echo "\n❌ Erreur HTTP {$status}\n";
        }
        exit(1);
    }
    
} catch (\Illuminate\Http\Client\ConnectionException $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "3. Résultat:\n";
    echo "   ❌ TIMEOUT après {$duration}ms\n";
    echo "   Erreur: " . $e->getMessage() . "\n";
    echo "\n💡 PROBLÈME: L'API CinetPay ne répond pas dans les délais.\n";
    echo "   Solutions:\n";
    echo "   1. Vérifier la connectivité réseau du serveur\n";
    echo "   2. Vérifier les règles de firewall\n";
    echo "   3. Augmenter les timeouts si le réseau est lent\n";
    exit(1);
} catch (\Exception $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "3. Résultat:\n";
    echo "   ❌ Erreur après {$duration}ms: " . $e->getMessage() . "\n";
    exit(1);
}

