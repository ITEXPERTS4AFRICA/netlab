<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CinetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CinetPayConfigController extends Controller
{
    /**
     * Afficher la page de configuration CinetPay
     */
    public function index(): Response
    {
        // Lire depuis la base de données, avec fallback sur .env
        $apiKey = Setting::get('cinetpay.api_key', config('services.cinetpay.api_key') ?? env('CINETPAY_API_KEY'));
        $secretKey = Setting::get('cinetpay.secret_key', config('services.cinetpay.secret_key') ?? env('CINETPAY_SECRET_KEY'));
        $siteId = Setting::get('cinetpay.site_id', config('services.cinetpay.site_id') ?? env('CINETPAY_SITE_ID'));
        $apiUrl = Setting::get('cinetpay.api_url', config('services.cinetpay.api_url') ?? env('CINETPAY_API_URL', 'https://api-checkout.cinetpay.com'));
        $notifyUrl = Setting::get('cinetpay.notify_url', config('services.cinetpay.notify_url') ?? env('CINETPAY_NOTIFY_URL'));
        $returnUrl = Setting::get('cinetpay.return_url', config('services.cinetpay.return_url') ?? env('CINETPAY_RETURN_URL'));
        $cancelUrl = Setting::get('cinetpay.cancel_url', config('services.cinetpay.cancel_url') ?? env('CINETPAY_CANCEL_URL'));
        $mode = Setting::get('cinetpay.mode', config('services.cinetpay.mode') ?? env('CINETPAY_MODE', 'sandbox'));

        // Générer les URLs par défaut si non définies (utiliser l'IP de production)
        $appUrl = config('app.url', 'http://10.10.10.20');
        // Si APP_URL contient une IP de production, l'utiliser, sinon utiliser 10.10.10.20
        if (strpos($appUrl, '10.10.10.20') === false && strpos($appUrl, '192.168.') === false && strpos($appUrl, 'localhost') === false) {
            // Si l'URL ne contient pas d'IP locale, utiliser l'IP de production
            $appUrl = 'http://10.10.10.20';
        }
        if (!$notifyUrl) {
            $notifyUrl = rtrim($appUrl, '/') . '/api/payments/cinetpay/webhook';
        }
        if (!$returnUrl) {
            $returnUrl = rtrim($appUrl, '/') . '/api/payments/return';
        }
        if (!$cancelUrl) {
            $cancelUrl = rtrim($appUrl, '/') . '/api/payments/cancel';
        }

        return Inertia::render('admin/cinetpay-config/index', [
            'config' => [
                'api_key' => $apiKey ?? '',
                'secret_key' => $secretKey ? '••••••••' : null, // Masquer la clé secrète
                'site_id' => $siteId ?? '',
                'api_url' => $apiUrl ?? 'https://api-checkout.cinetpay.com',
                'notify_url' => $notifyUrl ?? '',
                'return_url' => $returnUrl ?? '',
                'cancel_url' => $cancelUrl ?? '',
                'mode' => $mode ?? 'sandbox',
            ],
        ]);
    }

    /**
     * Mettre à jour la configuration CinetPay
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'api_key' => ['required', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'min:1'],
            'site_id' => ['required', 'string', 'max:255'],
            'api_url' => ['required', 'url'],
            'notify_url' => ['required', 'url'],
            'return_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
            'mode' => ['required', 'string', 'in:sandbox,production,test,prod'],
        ]);

        // Normaliser le mode
        $mode = strtolower(trim($validated['mode']));
        if (in_array($mode, ['production', 'prod'])) {
            $mode = 'production';
        } elseif (in_array($mode, ['sandbox', 'test'])) {
            $mode = 'sandbox';
        } else {
            $mode = 'sandbox';
        }

        // Vérifier qu'une clé secrète existe déjà si aucune nouvelle n'est fournie
        $hasExistingSecretKey = Setting::where('key', 'cinetpay.secret_key')->exists();
        if (empty($validated['secret_key']) && !$hasExistingSecretKey) {
            return redirect()->back()
                ->withErrors(['secret_key' => 'La clé secrète est requise pour la première configuration.']);
        }

        // Sauvegarder dans la base de données
        Setting::set('cinetpay.api_key', $validated['api_key'], 'string', 'Clé API CinetPay');
        Setting::set('cinetpay.site_id', $validated['site_id'], 'string', 'ID du site CinetPay');
        Setting::set('cinetpay.api_url', $validated['api_url'], 'string', 'URL de l\'API CinetPay');
        Setting::set('cinetpay.notify_url', $validated['notify_url'], 'string', 'URL de notification webhook CinetPay');
        Setting::set('cinetpay.return_url', $validated['return_url'], 'string', 'URL de retour après paiement CinetPay');
        Setting::set('cinetpay.cancel_url', $validated['cancel_url'], 'string', 'URL d\'annulation de paiement CinetPay');
        Setting::set('cinetpay.mode', $mode, 'string', 'Mode CinetPay (sandbox/production)');

        // Ne mettre à jour la clé secrète que si elle est fournie
        if (!empty($validated['secret_key'])) {
            Setting::set('cinetpay.secret_key', $validated['secret_key'], 'string', 'Clé secrète CinetPay', true); // Crypter
        }

        // Mettre à jour aussi le .env pour compatibilité (optionnel)
        $this->syncToEnv($validated, $mode);

        // Recharger la configuration
        Artisan::call('config:clear');

        return redirect()->back()
            ->with('success', 'Configuration CinetPay mise à jour avec succès.');
    }

    /**
     * Tester la configuration CinetPay
     */
    public function testConnection(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Créer une instance temporaire du service avec les paramètres fournis
            $config = [
                'api_key' => $request->input('api_key') 
                    ?? Setting::get('cinetpay.api_key') 
                    ?? config('services.cinetpay.api_key'),
                'site_id' => $request->input('site_id')
                    ?? Setting::get('cinetpay.site_id')
                    ?? config('services.cinetpay.site_id'),
                'api_url' => $request->input('api_url')
                    ?? Setting::get('cinetpay.api_url')
                    ?? config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com'),
                'notify_url' => $request->input('notify_url')
                    ?? Setting::get('cinetpay.notify_url')
                    ?? config('services.cinetpay.notify_url'),
                'return_url' => $request->input('return_url')
                    ?? Setting::get('cinetpay.return_url')
                    ?? config('services.cinetpay.return_url'),
                'cancel_url' => $request->input('cancel_url')
                    ?? Setting::get('cinetpay.cancel_url')
                    ?? config('services.cinetpay.cancel_url'),
                'mode' => $request->input('mode')
                    ?? Setting::get('cinetpay.mode')
                    ?? config('services.cinetpay.mode', 'sandbox'),
            ];

            if (empty($config['api_key']) || empty($config['site_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key et Site ID sont requis pour tester la connexion.',
                ], 400);
            }

            // Utiliser le service de santé existant
            $cinetPayService = app(CinetPayService::class);
            $health = $cinetPayService->checkHealth();

            // Nettoyer les données de santé pour éviter les problèmes d'encodage JSON
            $cleanedHealth = $this->cleanHealthData($health);

            $isHealthy = $health['overall_health'] === 'healthy';
            
            return response()->json([
                'success' => $isHealthy,
                'message' => $isHealthy
                    ? 'Configuration CinetPay valide et connectée !' 
                    : 'Problème de configuration ou de connexion CinetPay.',
                'health' => $cleanedHealth,
            ], $isHealthy ? 200 : 503)->header('Content-Type', 'application/json; charset=utf-8');

        } catch (\Exception $e) {
            Log::error('Erreur lors du test de connexion CinetPay', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connexion : ' . $e->getMessage(),
                'details' => app()->environment('local') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    /**
     * Synchroniser la configuration vers le fichier .env (pour compatibilité)
     */
    private function syncToEnv(array $validated, string $mode): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        // Mettre à jour les variables dans .env
        $envContent = $this->updateEnvVariable($envContent, 'CINETPAY_API_KEY', $validated['api_key']);
        $envContent = $this->updateEnvVariable($envContent, 'CINETPAY_SITE_ID', $validated['site_id']);
        $envContent = $this->updateEnvVariable($envContent, 'CINETPAY_API_URL', $validated['api_url']);
        $envContent = $this->updateEnvVariable($envContent, 'CINETPAY_NOTIFY_URL', $validated['notify_url']);
        $envContent = $this->updateEnvVariable($envContent, 'CINETPAY_RETURN_URL', $validated['return_url']);
        $envContent = $this->updateEnvVariable($envContent, 'CINETPAY_CANCEL_URL', $validated['cancel_url']);
        $envContent = $this->updateEnvVariable($envContent, 'CINETPAY_MODE', $mode);

        // Ne mettre à jour la clé secrète que si elle est fournie
        if (!empty($validated['secret_key'])) {
            $envContent = $this->updateEnvVariable($envContent, 'CINETPAY_SECRET_KEY', $validated['secret_key']);
        }

        File::put($envPath, $envContent);
    }

    /**
     * Mettre à jour une variable dans le fichier .env
     */
    private function updateEnvVariable(string $envContent, string $key, string $value): string
    {
        // Échapper les caractères spéciaux pour la valeur
        $escapedValue = str_replace(['\\', '$'], ['\\\\', '\\$'], $value);

        // Si la variable existe déjà, la remplacer
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            return preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$escapedValue}",
                $envContent
            );
        }

        // Sinon, l'ajouter à la fin
        return $envContent . "\n{$key}={$escapedValue}\n";
    }

    /**
     * Nettoyer les données de santé pour éviter les problèmes d'encodage JSON
     */
    private function cleanHealthData(array $health): array
    {
        // Remplacer les caractères spéciaux par des équivalents ASCII
        if (isset($health['configuration'])) {
            foreach ($health['configuration'] as $key => $value) {
                if (is_string($value)) {
                    // Remplacer les caractères Unicode par des équivalents ASCII
                    $health['configuration'][$key] = str_replace(
                        ['✓', '✗'],
                        ['OK', 'KO'],
                        $value
                    );
                }
            }
        }

        // Limiter la taille des messages d'erreur si nécessaire
        if (isset($health['connectivity']['error']) && strlen($health['connectivity']['error']) > 500) {
            $health['connectivity']['error'] = substr($health['connectivity']['error'], 0, 500) . '...';
        }

        if (isset($health['api_status']['error']) && strlen($health['api_status']['error']) > 500) {
            $health['api_status']['error'] = substr($health['api_status']['error'], 0, 500) . '...';
        }

        return $health;
    }
}

