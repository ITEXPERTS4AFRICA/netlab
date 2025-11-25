<?php

/**
 * Script de diagnostic complet pour CinetPay
 * Vérifie la configuration, le SDK et la connexion
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;
use App\Services\CinetPayService;
use Illuminate\Support\Facades\Log;

echo "🔍 DIAGNOSTIC CINETPAY\n";
echo str_repeat("=", 60) . "\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Vérifier la présence du SDK
echo "1. Vérification du SDK CinetPay...\n";
$sdkPath = base_path('cinetpay-php-sdk-master/src/cinetpay.php');
if (file_exists($sdkPath)) {
    $success[] = "✅ SDK trouvé : $sdkPath";
    echo "   ✅ SDK trouvé\n";
    
    // Vérifier que la classe peut être chargée
    if (!class_exists('CinetPay')) {
        require_once $sdkPath;
    }
    
    if (class_exists('CinetPay')) {
        $success[] = "✅ Classe CinetPay chargée";
        echo "   ✅ Classe CinetPay chargée\n";
    } else {
        $errors[] = "❌ Impossible de charger la classe CinetPay";
        echo "   ❌ Impossible de charger la classe CinetPay\n";
    }
} else {
    $errors[] = "❌ SDK non trouvé : $sdkPath";
    echo "   ❌ SDK non trouvé : $sdkPath\n";
}
echo "\n";

// 2. Vérifier la configuration depuis .env
echo "2. Vérification de la configuration (.env)...\n";
$apiKey = env('CINETPAY_API_KEY', '');
$siteId = env('CINETPAY_SITE_ID', '');
$mode = env('CINETPAY_MODE', 'sandbox');
$notifyUrl = env('CINETPAY_NOTIFY_URL');
$returnUrl = env('CINETPAY_RETURN_URL');
$cancelUrl = env('CINETPAY_CANCEL_URL');

if (empty($apiKey)) {
    $warnings[] = "⚠️  CINETPAY_API_KEY non défini dans .env";
    echo "   ⚠️  CINETPAY_API_KEY non défini\n";
} else {
    if ($apiKey === 'temp_key' || strlen($apiKey) < 10) {
        $warnings[] = "⚠️  CINETPAY_API_KEY semble invalide (trop court ou valeur temporaire)";
        echo "   ⚠️  CINETPAY_API_KEY semble invalide\n";
    } else {
        $success[] = "✅ CINETPAY_API_KEY défini (" . substr($apiKey, 0, 8) . "...)";
        echo "   ✅ CINETPAY_API_KEY défini\n";
    }
}

if (empty($siteId)) {
    $warnings[] = "⚠️  CINETPAY_SITE_ID non défini dans .env";
    echo "   ⚠️  CINETPAY_SITE_ID non défini\n";
} else {
    if ($siteId === 'temp_site' || strlen($siteId) < 3) {
        $warnings[] = "⚠️  CINETPAY_SITE_ID semble invalide";
        echo "   ⚠️  CINETPAY_SITE_ID semble invalide\n";
    } else {
        $success[] = "✅ CINETPAY_SITE_ID défini ($siteId)";
        echo "   ✅ CINETPAY_SITE_ID défini\n";
    }
}

echo "   Mode : $mode\n";
if (!in_array(strtolower($mode), ['sandbox', 'test', 'production', 'prod'])) {
    $warnings[] = "⚠️  Mode invalide : $mode (devrait être sandbox ou production)";
}

if (empty($notifyUrl)) {
    echo "   ℹ️  CINETPAY_NOTIFY_URL non défini (sera généré automatiquement)\n";
} else {
    echo "   ✅ CINETPAY_NOTIFY_URL : $notifyUrl\n";
}

if (empty($returnUrl)) {
    echo "   ℹ️  CINETPAY_RETURN_URL non défini (sera généré automatiquement)\n";
} else {
    echo "   ✅ CINETPAY_RETURN_URL : $returnUrl\n";
}

if (empty($cancelUrl)) {
    echo "   ℹ️  CINETPAY_CANCEL_URL non défini (sera généré automatiquement)\n";
} else {
    echo "   ✅ CINETPAY_CANCEL_URL : $cancelUrl\n";
}
echo "\n";

// 3. Vérifier la configuration depuis la base de données
echo "3. Vérification de la configuration (Base de données)...\n";
try {
    $dbApiKey = Setting::get('cinetpay.api_key');
    $dbSiteId = Setting::get('cinetpay.site_id');
    $dbMode = Setting::get('cinetpay.mode');
    
    if (!empty($dbApiKey)) {
        echo "   ✅ API Key en DB : " . substr($dbApiKey, 0, 8) . "...\n";
        $success[] = "✅ Configuration trouvée en base de données";
    } else {
        echo "   ℹ️  Aucune API Key en base de données (utilise .env)\n";
    }
    
    if (!empty($dbSiteId)) {
        echo "   ✅ Site ID en DB : $dbSiteId\n";
    } else {
        echo "   ℹ️  Aucun Site ID en base de données (utilise .env)\n";
    }
    
    if (!empty($dbMode)) {
        echo "   ✅ Mode en DB : $dbMode\n";
    } else {
        echo "   ℹ️  Aucun mode en base de données (utilise .env)\n";
    }
} catch (\Exception $e) {
    $warnings[] = "⚠️  Erreur lors de la lecture de la base de données : " . $e->getMessage();
    echo "   ⚠️  Erreur : " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Tester l'initialisation du service
echo "4. Test d'initialisation du service CinetPayService...\n";
try {
    $cinetPayService = new CinetPayService();
    
    // Utiliser la réflexion pour accéder aux propriétés protégées
    $reflection = new ReflectionClass($cinetPayService);
    
    $apiKeyProp = $reflection->getProperty('apiKey');
    $apiKeyProp->setAccessible(true);
    $serviceApiKey = $apiKeyProp->getValue($cinetPayService);
    
    $siteIdProp = $reflection->getProperty('siteId');
    $siteIdProp->setAccessible(true);
    $serviceSiteId = $siteIdProp->getValue($cinetPayService);
    
    $modeProp = $reflection->getProperty('mode');
    $modeProp->setAccessible(true);
    $serviceMode = $modeProp->getValue($cinetPayService);
    
    $cinetPayProp = $reflection->getProperty('cinetPay');
    $cinetPayProp->setAccessible(true);
    $cinetPayInstance = $cinetPayProp->getValue($cinetPayService);
    
    echo "   API Key chargée : " . (!empty($serviceApiKey) ? substr($serviceApiKey, 0, 8) . "..." : "VIDE") . "\n";
    echo "   Site ID chargé : " . ($serviceSiteId ?? "VIDE") . "\n";
    echo "   Mode chargé : " . ($serviceMode ?? "VIDE") . "\n";
    
    if ($cinetPayInstance === null) {
        $errors[] = "❌ SDK CinetPay non initialisé dans le service";
        echo "   ❌ SDK CinetPay non initialisé\n";
        echo "   Raison probable : Credentials manquants ou invalides\n";
    } else {
        $success[] = "✅ SDK CinetPay initialisé avec succès";
        echo "   ✅ SDK CinetPay initialisé\n";
    }
} catch (\Exception $e) {
    $errors[] = "❌ Erreur lors de l'initialisation : " . $e->getMessage();
    echo "   ❌ Erreur : " . $e->getMessage() . "\n";
    echo "   Trace : " . substr($e->getTraceAsString(), 0, 200) . "...\n";
}
echo "\n";

// 5. Tester la connexion à l'API CinetPay
echo "5. Test de connexion à l'API CinetPay...\n";
if (!empty($apiKey) && !empty($siteId) && $apiKey !== 'temp_key' && $siteId !== 'temp_site') {
    try {
        $platform = strtoupper($mode) === 'PRODUCTION' ? 'PROD' : 'TEST';
        $version = 'V2';
        
        if (class_exists('CinetPay')) {
            $cinetPay = new \CinetPay($siteId, $apiKey, $platform, $version, ['style' => false]);
            
            // Tester l'obtention d'une signature (test minimal)
            echo "   Tentative de connexion à l'API...\n";
            
            // Configurer une transaction de test
            $testTransId = 'TEST_' . time();
            $cinetPay->setTransId($testTransId)
                ->setDesignation('Test de connexion')
                ->setTransDate(date('Y-m-d H:i:s'))
                ->setAmount(100) // Montant minimum
                ->setCurrency('XOF')
                ->setDebug(false);
            
            // Essayer d'obtenir la signature (avec timeout court)
            $startTime = microtime(true);
            try {
                $signature = @$cinetPay->getSignature();
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                if (!empty($signature) && !is_array($signature)) {
                    $success[] = "✅ Connexion à l'API réussie (${duration}ms)";
                    echo "   ✅ Connexion réussie en ${duration}ms\n";
                } else {
                    $errors[] = "❌ Erreur API : " . (is_array($signature) ? json_encode($signature) : 'Réponse invalide');
                    echo "   ❌ Erreur API : " . (is_array($signature) ? json_encode($signature) : 'Réponse invalide') . "\n";
                }
            } catch (\Exception $e) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $errorMsg = $e->getMessage();
                
                if (stripos($errorMsg, 'timeout') !== false) {
                    $warnings[] = "⚠️  Timeout de connexion (${duration}ms) - L'API sandbox peut être lente";
                    echo "   ⚠️  Timeout après ${duration}ms\n";
                    echo "   ℹ️  L'API sandbox peut être lente ou indisponible\n";
                } else {
                    $errors[] = "❌ Erreur : $errorMsg";
                    echo "   ❌ Erreur : $errorMsg\n";
                }
            }
        } else {
            $errors[] = "❌ Classe CinetPay non disponible";
            echo "   ❌ Classe CinetPay non disponible\n";
        }
    } catch (\Exception $e) {
        $errors[] = "❌ Erreur lors du test de connexion : " . $e->getMessage();
        echo "   ❌ Erreur : " . $e->getMessage() . "\n";
    }
} else {
    $warnings[] = "⚠️  Impossible de tester la connexion - Credentials manquants";
    echo "   ⚠️  Credentials manquants - Test de connexion ignoré\n";
}
echo "\n";

// 6. Vérifier les URLs générées
echo "6. Vérification des URLs de callback...\n";
$appUrl = config('app.url', 'http://localhost:8000');
echo "   APP_URL : $appUrl\n";

$notifyUrlFinal = $notifyUrl ?? url('/api/payments/cinetpay/webhook');
$returnUrlFinal = $returnUrl ?? url('/api/payments/return');
$cancelUrlFinal = $cancelUrl ?? url('/api/payments/cancel');

echo "   Notify URL : $notifyUrlFinal\n";
echo "   Return URL : $returnUrlFinal\n";
echo "   Cancel URL : $cancelUrlFinal\n";

// Vérifier que les routes existent
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$hasWebhook = false;
$hasReturn = false;
$hasCancel = false;

foreach ($routes as $route) {
    $uri = $route->uri();
    if (strpos($uri, 'cinetpay/webhook') !== false) {
        $hasWebhook = true;
    }
    if (strpos($uri, 'payments/return') !== false) {
        $hasReturn = true;
    }
    if (strpos($uri, 'payments/cancel') !== false) {
        $hasCancel = true;
    }
}

if ($hasWebhook) {
    echo "   ✅ Route webhook trouvée\n";
} else {
    $warnings[] = "⚠️  Route webhook non trouvée";
    echo "   ⚠️  Route webhook non trouvée\n";
}

if ($hasReturn) {
    echo "   ✅ Route return trouvée\n";
} else {
    $warnings[] = "⚠️  Route return non trouvée";
    echo "   ⚠️  Route return non trouvée\n";
}

if ($hasCancel) {
    echo "   ✅ Route cancel trouvée\n";
} else {
    $warnings[] = "⚠️  Route cancel non trouvée";
    echo "   ⚠️  Route cancel non trouvée\n";
}
echo "\n";

// Résumé
echo str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ\n";
echo str_repeat("=", 60) . "\n\n";

if (count($success) > 0) {
    echo "✅ SUCCÈS (" . count($success) . ")\n";
    foreach ($success as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  AVERTISSEMENTS (" . count($warnings) . ")\n";
    foreach ($warnings as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "❌ ERREURS (" . count($errors) . ")\n";
    foreach ($errors as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

// Recommandations
echo "💡 RECOMMANDATIONS\n";
echo str_repeat("-", 60) . "\n";

if (empty($apiKey) || $apiKey === 'temp_key') {
    echo "1. Définir CINETPAY_API_KEY dans .env ou via l'interface admin\n";
}

if (empty($siteId) || $siteId === 'temp_site') {
    echo "2. Définir CINETPAY_SITE_ID dans .env ou via l'interface admin\n";
}

if (count($errors) === 0 && count($warnings) === 0) {
    echo "✅ Configuration correcte ! CinetPay devrait fonctionner.\n";
} else {
    echo "⚠️  Corriger les erreurs et avertissements ci-dessus.\n";
}

echo "\n";
echo "📝 Pour configurer via l'interface admin :\n";
echo "   http://localhost:8000/admin/settings/cinetpay\n";
echo "\n";

