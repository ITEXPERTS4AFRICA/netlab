#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📊 Comparaison des stats CML vs Application\n";
echo str_repeat("━", 80) . "\n\n";

// 1. Récupérer les labs depuis l'API CML directement
$username = env('CML_USERNAME', 'cheick');
$password = env('CML_PASSWORD', 'cheick2025');
$baseUrl = config('services.cml.base_url');

echo "📡 Serveur CML:\n";
$token = null;
try {
    $authResponse = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
        ->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        ->post("{$baseUrl}/v0/auth_extended", [
            'username' => $username,
            'password' => $password,
        ]);

    if ($authResponse->successful()) {
        $authData = $authResponse->json();
        $token = $authData['token'] ?? null;

        if ($token) {
            $labsResponse = \Illuminate\Support\Facades\Http::withToken($token)
                ->withOptions(['verify' => false])
                ->get("{$baseUrl}/v0/labs?show_all=true");

            if ($labsResponse->successful()) {
                $labs = $labsResponse->json();
                $cmlLabsCount = is_array($labs) ? count($labs) : 0;
                echo "   ✅ Nombre de labs: $cmlLabsCount\n";
            } else {
                echo "   ❌ Erreur lors de la récupération des labs: " . $labsResponse->status() . "\n";
                $cmlLabsCount = 0;
            }
        } else {
            echo "   ❌ Token non trouvé dans la réponse\n";
            $cmlLabsCount = 0;
        }
    } else {
        echo "   ❌ Erreur d'authentification: " . $authResponse->status() . "\n";
        $cmlLabsCount = 0;
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    $cmlLabsCount = 0;
}

echo "\n";

// 2. Stats depuis la base de données
echo "💾 Base de données locale:\n";
$dbLabsCount = \App\Models\Lab::count();
$reservationsCount = \App\Models\Reservation::count();
$activeReservations = \App\Models\Reservation::where('status', 'active')
    ->where('end_at', '>', now())
    ->count();
$completedReservations = \App\Models\Reservation::where('status', 'completed')->count();

echo "   Labs en DB: $dbLabsCount\n";
echo "   Reservations: $reservationsCount\n";
echo "   Reservations actives: $activeReservations\n";
echo "   Reservations complétées: $completedReservations\n";

echo "\n";

// 3. Comparaison
echo "📊 Comparaison:\n";
echo "   CML API: $cmlLabsCount labs\n";
echo "   Base de données: $dbLabsCount labs\n";
echo "   Différence: " . ($cmlLabsCount - $dbLabsCount) . " labs\n";

echo "\n";

// 4. Ce que l'application devrait afficher
echo "🎯 Ce que l'application affiche:\n";
if ($cmlLabsCount > 0) {
    echo "   totalLabs: $cmlLabsCount (depuis l'API CML)\n";
    echo "   availableLabs: " . max(0, $cmlLabsCount - $activeReservations) . "\n";
    echo "   occupiedLabs: $activeReservations\n";
} else {
    echo "   ⚠️  totalLabs: 0 (l'API CML ne retourne pas de labs ou erreur)\n";
}

if ($dbLabsCount == 0 && $cmlLabsCount > 0) {
    echo "\n";
    echo "💡 Recommandation:\n";
    echo "   Les labs ne sont pas synchronisés dans la base de données locale.\n";
    echo "   L'application affiche les labs depuis l'API CML en temps réel.\n";
    echo "   Pour synchroniser, exécutez: php artisan cml:sync-labs\n";
}


