<?php

/**
 * Script pour corriger automatiquement l'URL CinetPay
 * Usage: php fix-cinetpay-url.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "🔧 Correction de l'URL CinetPay\n";
echo str_repeat("=", 60) . "\n\n";

$correctUrl = 'https://api-checkout.cinetpay.com';
$currentUrl = env('CINETPAY_API_URL', '');

echo "1. État actuel:\n";
echo "   URL actuelle: " . ($currentUrl ?: 'NON DÉFINI') . "\n";
echo "   URL correcte: {$correctUrl}\n\n";

if ($currentUrl === $correctUrl) {
    echo "✅ L'URL est déjà correcte !\n";
    echo "\nSi le problème persiste, videz le cache:\n";
    echo "   php artisan config:clear\n";
    exit(0);
}

// 2. Mettre à jour dans la base de données (si disponible)
echo "2. Mise à jour dans la base de données...\n";
try {
    Setting::set('cinetpay.api_url', $correctUrl);
    echo "   ✅ URL mise à jour dans la base de données\n";
} catch (\Exception $e) {
    echo "   ⚠️  Impossible de mettre à jour dans la base de données: " . $e->getMessage() . "\n";
    echo "   💡 Cela peut être normal si la table settings n'existe pas encore\n";
}

// 3. Vider le cache
echo "\n3. Vidage du cache...\n";
try {
    \Artisan::call('config:clear');
    echo "   ✅ Cache de configuration vidé\n";
    
    \Artisan::call('cache:clear');
    echo "   ✅ Cache applicatif vidé\n";
} catch (\Exception $e) {
    echo "   ⚠️  Erreur lors du vidage du cache: " . $e->getMessage() . "\n";
}

// 4. Vérifier la nouvelle configuration
echo "\n4. Vérification de la nouvelle configuration...\n";
$newConfig = config('services.cinetpay');
$newUrl = $newConfig['api_url'] ?? '';

if ($newUrl === $correctUrl) {
    echo "   ✅ Configuration mise à jour avec succès !\n";
    echo "   Nouvelle URL: {$newUrl}\n";
} else {
    echo "   ⚠️  La configuration n'a pas été mise à jour\n";
    echo "   URL actuelle: {$newUrl}\n";
    echo "\n   💡 ACTION MANUELLE REQUISE:\n";
    echo "   1. Modifiez le fichier .env:\n";
    echo "      CINETPAY_API_URL={$correctUrl}\n";
    echo "   2. Videz le cache:\n";
    echo "      php artisan config:clear\n";
    exit(1);
}

// 5. Test de l'URL
echo "\n5. Test de l'URL corrigée...\n";
$testUrl = $correctUrl . '/v2/payment';
echo "   URL à tester: {$testUrl}\n";

$apiKey = Setting::get('cinetpay.api_key', env('CINETPAY_API_KEY', ''));
$siteId = Setting::get('cinetpay.site_id', env('CINETPAY_SITE_ID', ''));

if (empty($apiKey) || empty($siteId)) {
    echo "   ⚠️  Configuration incomplète, test impossible\n";
} else {
    echo "   Test en cours...\n";
    
    try {
        $response = \Illuminate\Support\Facades\Http::asJson()
            ->timeout(15)
            ->connectTimeout(5)
            ->withoutVerifying()
            ->post($testUrl, [
                'apikey' => $apiKey,
                'site_id' => $siteId,
                'transaction_id' => 'TEST_' . time(),
                'amount' => 100,
                'currency' => 'XOF',
                'description' => 'Test',
            ]);
        
        $status = $response->status();
        $body = $response->body();
        
        if ($status === 200 && !stripos($body, '<!DOCTYPE')) {
            $data = json_decode($body, true);
            if ($data && isset($data['data']['payment_url'])) {
                echo "   ✅ L'URL fonctionne correctement !\n";
                echo "   ✅ Test réussi - CinetPay est opérationnel\n";
            } else {
                echo "   ⚠️  Réponse reçue mais format inattendu\n";
            }
        } else {
            echo "   ⚠️  Status: {$status}\n";
            if (stripos($body, '<!DOCTYPE') !== false) {
                echo "   ❌ L'API retourne encore du HTML\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ⚠️  Erreur lors du test: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Correction terminée !\n";
echo "\n";
echo "⚠️  IMPORTANT: Si vous avez modifié le .env manuellement,\n";
echo "   exécutez aussi:\n";
echo "   php artisan config:clear\n";
echo "   php artisan cache:clear\n";

