<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CiscoApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CmlConfigController extends Controller
{
    /**
     * Afficher la page de configuration CML
     */
    public function index(): Response
    {
        // Lire depuis la base de données, avec fallback sur .env
        $baseUrl = Setting::get('cml.base_url', config('services.cml.base_url') ?? env('CML_API_BASE_URL'));
        $username = Setting::get('cml.username', env('CML_USERNAME'));
        $password = Setting::get('cml.password', env('CML_PASSWORD'));

        return Inertia::render('admin/cml-config/index', [
            'config' => [
                'base_url' => $baseUrl ?? '',
                'username' => $username ?? '',
                'password' => $password ? '••••••••' : null, // Masquer le mot de passe
            ],
        ]);
    }

    /**
     * Mettre à jour la configuration CML
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'base_url' => ['required', 'url'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:1'],
        ]);

        // Sauvegarder dans la base de données
        Setting::set('cml.base_url', $validated['base_url'], 'string', 'URL de base de l\'API CML');
        Setting::set('cml.username', $validated['username'], 'string', 'Nom d\'utilisateur CML');

        // Ne mettre à jour le mot de passe que s'il est fourni
        if (!empty($validated['password'])) {
            Setting::set('cml.password', $validated['password'], 'string', 'Mot de passe CML', true); // Crypter le mot de passe
        }

        // Mettre à jour aussi le .env pour compatibilité (optionnel)
        $this->syncToEnv($validated);

        // Recharger la configuration
        Artisan::call('config:clear');

        return redirect()->back()
            ->with('success', 'Configuration CML mise à jour avec succès.');
    }

    /**
     * Tester la connexion CML
     */
    public function testConnection(Request $request, CiscoApiService $cmlService): \Illuminate\Http\JsonResponse
    {
        // Priorité : données du formulaire > base de données > .env
        $baseUrl = $request->input('base_url')
            ?? Setting::get('cml.base_url')
            ?? config('services.cml.base_url');

        $username = $request->input('username')
            ?? Setting::get('cml.username')
            ?? env('CML_USERNAME');

        $password = $request->input('password')
            ?? Setting::get('cml.password')
            ?? env('CML_PASSWORD');

        if (!$baseUrl || !$username || !$password) {
            return response()->json([
                'success' => false,
                'message' => 'Tous les champs sont requis pour tester la connexion.',
            ], 400);
        }

        try {
            // S'assurer que l'URL de base ne contient pas /api
            $baseUrl = rtrim($baseUrl, '/');
            if (str_ends_with($baseUrl, '/api')) {
                $baseUrl = rtrim($baseUrl, '/api');
            }

            // Mettre à jour temporairement la config pour le test
            config(['services.cml.base_url' => $baseUrl]);
            $cmlService->setBaseUrl($baseUrl);

            // Tester l'authentification
            $result = $cmlService->auth_extended($username, $password);

            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                    'details' => $result,
                ], 401);
            }

            // Vérifier que le token est présent
            $token = $result['token'] ?? null;
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun token reçu de l\'API CML.',
                    'details' => $result,
                ], 401);
            }

            // Tester un appel API avec le token
            $cmlService->setToken($token);
            $labs = $cmlService->getLabs();

            if (isset($labs['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connexion réussie mais erreur lors de la récupération des labs.',
                    'auth_success' => true,
                    'details' => $labs,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Connexion CML réussie !',
                'token' => $token, // Token complet pour affichage et copie
                'labs_count' => is_array($labs) ? count($labs) : 0,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors du test de connexion CML', [
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
    private function syncToEnv(array $validated): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        // Mettre à jour les variables dans .env
        $envContent = $this->updateEnvVariable($envContent, 'CML_API_BASE_URL', $validated['base_url']);
        $envContent = $this->updateEnvVariable($envContent, 'CML_USERNAME', $validated['username']);

        // Ne mettre à jour le mot de passe que s'il est fourni
        if (!empty($validated['password'])) {
            $envContent = $this->updateEnvVariable($envContent, 'CML_PASSWORD', $validated['password']);
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
}
