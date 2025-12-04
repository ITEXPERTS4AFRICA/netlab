<?php

namespace App\Console\Commands;

use App\Models\Lab;
use App\Services\Cisco\LabService;
use App\Services\Cisco\AuthService;
use App\Helpers\CmlConfigHelper;
use Illuminate\Console\Command;

class SyncCmlLabs extends Command
{
    protected $signature = 'cml:sync-labs {--force}';
    protected $description = 'Sync labs from CML into local database';

    public function handle(LabService $labService)
    {
        $this->info('🔄 Démarrage de la synchronisation des labs CML...');

        // Obtenir les credentials depuis la configuration
        if (!CmlConfigHelper::isConfigured()) {
            $this->error('❌ Configuration CML incomplète.');
            return 1;
        }

        $credentials = CmlConfigHelper::getCredentials();
        $this->info("📡 Connexion à CML: {$credentials['base_url']}");

        // Authentifier
        $authService = new AuthService();
        $authService->setBaseUrl($credentials['base_url']);
        $authResult = $authService->authExtended($credentials['username'], $credentials['password']);

        if (isset($authResult['error']) || !isset($authResult['token'])) {
            $this->error('❌ Échec de l\'authentification: ' . ($authResult['error'] ?? 'Token non reçu'));
            return 2;
        }

        $token = $authResult['token'];
        $this->info('✅ Authentification réussie');

        // Configurer le service
        $labService->setBaseUrl($credentials['base_url']);
        $labService->setToken($token);

        // Récupérer les labs
        $this->info('📥 Récupération des labs depuis CML...');
        $cmlLabs = $labService->getLabs();

        if (isset($cmlLabs['error'])) {
            $this->error('❌ Erreur lors de la récupération: ' . ($cmlLabs['error'] ?? 'Erreur inconnue'));
            return 3;
        }

        if (!is_array($cmlLabs) || empty($cmlLabs)) {
            $this->warn('⚠️  Aucun lab trouvé dans CML');
            return 0;
        }

        $totalLabs = count($cmlLabs);
        $this->info("📊 {$totalLabs} lab(s) trouvé(s) dans CML");

        // Barre de progression
        $bar = $this->output->createProgressBar($totalLabs);
        $bar->start();

        $syncedCount = 0;
        $updatedCount = 0;
        $errorCount = 0;

        foreach ($cmlLabs as $labId) {
            $bar->advance();

            try {
                // Récupérer les détails du lab
                if (is_string($labId)) {
                    $labData = $labService->getLab($labId);
                } else {
                    $labData = $labId;
                }

                if (isset($labData['error'])) {
                    $errorCount++;
                    continue;
                }

                if (!isset($labData['id']) && !isset($labData['uuid'])) {
                    $errorCount++;
                    continue;
                }

                $cmlId = $labData['id'] ?? $labData['uuid'] ?? null;

                if (!$cmlId || !$this->isValidUuid($cmlId)) {
                    $errorCount++;
                    continue;
                }

                // Préparer les attributs
                $labAttributes = [
                    'cml_id' => $cmlId,
                    'lab_title' => $labData['lab_title'] ?? null,
                    'state' => $labData['state'] ?? null,
                    'node_count' => $labData['node_count'] ?? null,
                    'link_count' => $labData['link_count'] ?? null,
                    'owner' => $labData['owner'] ?? null,
                    'created' => $labData['created'] ?? null,
                    'modified' => $labData['modified'] ?? null,
                ];

                // Gérer lab_description (colonne JSON dans la DB, mais CML retourne une string)
                // PostgreSQL JSON nécessite un JSON valide, donc on convertit la string en JSON
                if (isset($labData['lab_description']) && !empty($labData['lab_description'])) {
                    if (is_string($labData['lab_description'])) {
                        // Convertir la string en JSON valide (string JSON)
                        $labAttributes['lab_description'] = json_encode($labData['lab_description'], JSON_UNESCAPED_UNICODE);
                    } elseif (is_array($labData['lab_description'])) {
                        // Déjà un array, Laravel le convertira en JSON
                        $labAttributes['lab_description'] = $labData['lab_description'];
                    }
                } else {
                    $labAttributes['lab_description'] = null;
                }

                // Gérer effective_permissions
                if (isset($labData['effective_permissions'])) {
                    if (is_array($labData['effective_permissions']) && !empty($labData['effective_permissions'])) {
                        $labAttributes['effective_permissions'] = $labData['effective_permissions'];
                    } else {
                        $labAttributes['effective_permissions'] = null;
                    }
                } else {
                    $labAttributes['effective_permissions'] = null;
                }

                // Créer ou mettre à jour
                $existingLab = Lab::where('cml_id', $cmlId)->first();

                if ($existingLab) {
                    $existingLab->update($labAttributes);
                    $updatedCount++;
                } else {
                    Lab::create($labAttributes);
                    $syncedCount++;
        }
            } catch (\Exception $e) {
                $errorCount++;
                $this->newLine();
                $this->warn("⚠️  Erreur pour lab {$labId}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Résumé
        $this->info('✅ Synchronisation terminée !');
        $this->table(
            ['Type', 'Nombre'],
            [
                ['Nouveaux labs', $syncedCount],
                ['Labs mis à jour', $updatedCount],
                ['Erreurs', $errorCount],
                ['Total traité', $syncedCount + $updatedCount],
            ]
        );

        $this->info("📊 Total labs dans la base: " . Lab::count());

        return 0;
    }

    private function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match('/^[\da-f]{8}-[\da-f]{4}-4[\da-f]{3}-[89ab][\da-f]{3}-[\da-f]{12}$/i', $uuid);
    }
}
