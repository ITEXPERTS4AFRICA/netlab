<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\CinetPayService;

class DiagnoseCinetPayProduction extends Command
{
    protected $signature = 'cinetpay:diagnose-production 
                            {--verbose : Afficher les détails complets}';

    protected $description = 'Diagnostic complet de CinetPay en production';

    public function handle(): int
    {
        $this->info('🔍 Diagnostic CinetPay Production');
        $this->newLine();

        $verbose = $this->option('verbose');

        // 1. Vérifier la configuration
        $this->info('1. Vérification de la configuration...');
        $this->checkConfiguration($verbose);

        // 2. Tester la connectivité réseau
        $this->newLine();
        $this->info('2. Test de connectivité réseau...');
        $this->testNetworkConnectivity($verbose);

        // 3. Tester l'endpoint de signature
        $this->newLine();
        $this->info('3. Test de l\'endpoint de signature...');
        $this->testSignatureEndpoint($verbose);

        // 4. Tester l'endpoint de paiement
        $this->newLine();
        $this->info('4. Test de l\'endpoint de paiement...');
        $this->testPaymentEndpoint($verbose);

        // 5. Vérifier les logs récents
        $this->newLine();
        $this->info('5. Analyse des logs récents...');
        $this->analyzeRecentLogs();

        // 6. Recommandations
        $this->newLine();
        $this->info('6. Recommandations...');
        $this->showRecommendations();

        return 0;
    }

    protected function checkConfiguration(bool $verbose): void
    {
        try {
            $service = new CinetPayService();
            
            // Utiliser la réflexion pour accéder aux propriétés privées
            $reflection = new \ReflectionClass($service);
            
            $apiKey = $reflection->getProperty('apiKey');
            $apiKey->setAccessible(true);
            $apiKeyValue = $apiKey->getValue($service);
            
            $siteId = $reflection->getProperty('siteId');
            $siteId->setAccessible(true);
            $siteIdValue = $siteId->getValue($service);
            
            $mode = $reflection->getProperty('mode');
            $mode->setAccessible(true);
            $modeValue = $mode->getValue($service);
            
            $apiUrl = $reflection->getProperty('apiUrl');
            $apiUrl->setAccessible(true);
            $apiUrlValue = $apiUrl->getValue($service);

            $this->line("   API Key: " . ($apiKeyValue ? substr($apiKeyValue, 0, 10) . '...' : '❌ NON DÉFINI'));
            $this->line("   Site ID: " . ($siteIdValue ? $siteIdValue : '❌ NON DÉFINI'));
            $this->line("   Mode: " . ($modeValue ?: '❌ NON DÉFINI'));
            $this->line("   API URL: " . ($apiUrlValue ?: '❌ NON DÉFINI'));

            if (empty($apiKeyValue) || empty($siteIdValue)) {
                $this->error('   ❌ Configuration incomplète !');
                return;
            }

            if ($apiKeyValue === 'temp_key' || $siteIdValue === 'temp_site') {
                $this->error('   ❌ Configuration temporaire détectée !');
                return;
            }

            $this->info('   ✅ Configuration de base OK');

            if ($verbose) {
                $this->line("   Notify URL: " . ($reflection->getProperty('notifyUrl')->getValue($service) ?: 'N/A'));
                $this->line("   Return URL: " . ($reflection->getProperty('returnUrl')->getValue($service) ?: 'N/A'));
                $this->line("   Cancel URL: " . ($reflection->getProperty('cancelUrl')->getValue($service) ?: 'N/A'));
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur: " . $e->getMessage());
        }
    }

    protected function testNetworkConnectivity(bool $verbose): void
    {
        $urls = [
            'https://api-checkout.cinetpay.com' => 'API Production',
            'https://api.sandbox.cinetpay.com' => 'API Sandbox',
            'https://www.google.com' => 'Test Internet général',
        ];

        foreach ($urls as $url => $label) {
            $this->line("   Test: {$label} ({$url})");
            
            $startTime = microtime(true);
            try {
                $response = Http::timeout(10)
                    ->connectTimeout(5)
                    ->get($url);
                
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                if ($response->successful()) {
                    $this->info("      ✅ Accessible ({$duration}ms)");
                } else {
                    $this->warn("      ⚠️  Réponse HTTP {$response->status()} ({$duration}ms)");
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $this->error("      ❌ Timeout ou connexion impossible ({$duration}ms)");
                if ($verbose) {
                    $this->line("         Erreur: " . $e->getMessage());
                }
            } catch (\Exception $e) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $this->error("      ❌ Erreur: {$e->getMessage()} ({$duration}ms)");
            }
        }
    }

    protected function testSignatureEndpoint(bool $verbose): void
    {
        try {
            $service = new CinetPayService();
            $reflection = new \ReflectionClass($service);
            
            $apiKey = $reflection->getProperty('apiKey')->getValue($service);
            $siteId = $reflection->getProperty('siteId')->getValue($service);
            $mode = $reflection->getProperty('mode')->getValue($service);
            
            if (empty($apiKey) || empty($siteId)) {
                $this->error('   ❌ Configuration manquante pour le test');
                return;
            }

            // Utiliser l'URL selon le mode
            $baseUrl = $mode === 'production' 
                ? 'https://api-checkout.cinetpay.com'
                : 'https://api.sandbox.cinetpay.com';
            
            $url = "{$baseUrl}/v2/?method=getSignatureByPost";
            
            $this->line("   URL: {$url}");
            $this->line("   Mode: {$mode}");

            $testData = [
                'apikey' => $apiKey,
                'cpm_site_id' => $siteId,
                'cpm_amount' => 100,
                'cpm_currency' => 'XOF',
                'cpm_trans_id' => 'TEST_' . time(),
            ];

            $startTime = microtime(true);
            
            try {
                $response = Http::timeout(15)
                    ->connectTimeout(5)
                    ->withoutVerifying()
                    ->asForm()
                    ->post($url, $testData);

                $duration = round((microtime(true) - $startTime) * 1000, 2);
                
                $this->line("   Temps de réponse: {$duration}ms");
                $this->line("   Status HTTP: " . $response->status());
                
                if ($response->successful()) {
                    $body = $response->body();
                    if (strlen($body) > 20) {
                        $this->info("   ✅ Réponse reçue (signature valide probable)");
                    } else {
                        $this->warn("   ⚠️  Réponse très courte: " . substr($body, 0, 100));
                    }
                    
                    if ($verbose) {
                        $this->line("   Réponse complète: " . substr($body, 0, 200));
                    }
                } else {
                    $this->error("   ❌ Erreur HTTP: " . $response->status());
                    if ($verbose) {
                        $this->line("   Réponse: " . $response->body());
                    }
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $this->error("   ❌ TIMEOUT après {$duration}ms");
                $this->error("   Erreur: " . $e->getMessage());
                $this->warn("   💡 L'API CinetPay ne répond pas. Vérifiez:");
                $this->line("      - La connectivité réseau du serveur");
                $this->line("      - Les règles de firewall");
                $this->line("      - Si l'API est temporairement indisponible");
            } catch (\Exception $e) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $this->error("   ❌ Erreur après {$duration}ms: " . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur lors du test: " . $e->getMessage());
        }
    }

    protected function testPaymentEndpoint(bool $verbose): void
    {
        $this->line("   Test de l'endpoint de paiement...");
        $this->warn("   ⚠️  Ce test nécessite des identifiants valides");
        
        // Ce test est similaire au test de signature mais avec plus de données
        // On peut le simplifier pour l'instant
        $this->line("   ℹ️  Utilisez 'cinetpay:test' pour un test complet");
    }

    protected function analyzeRecentLogs(): void
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!file_exists($logFile)) {
            $this->warn('   ⚠️  Fichier de log non trouvé');
            return;
        }

        // Lire les 100 dernières lignes
        $lines = file($logFile);
        $recentLines = array_slice($lines, -100);
        
        $cinetpayErrors = [];
        $timeoutErrors = [];
        
        foreach ($recentLines as $line) {
            if (stripos($line, 'cinetpay') !== false || stripos($line, 'CinetPay') !== false) {
                $cinetpayErrors[] = $line;
                
                if (stripos($line, 'timeout') !== false || stripos($line, 'timed out') !== false) {
                    $timeoutErrors[] = $line;
                }
            }
        }

        $this->line("   Erreurs CinetPay trouvées: " . count($cinetpayErrors));
        $this->line("   Erreurs de timeout: " . count($timeoutErrors));

        if (count($timeoutErrors) > 0) {
            $this->warn('   ⚠️  Des timeouts ont été détectés dans les logs récents');
            $this->line("   Dernière erreur timeout:");
            $this->line("   " . trim(end($timeoutErrors)));
        }
    }

    protected function showRecommendations(): void
    {
        $this->line("   📋 Recommandations:");
        $this->newLine();
        $this->line("   1. Vérifier la connectivité réseau du serveur:");
        $this->line("      curl -v https://api-checkout.cinetpay.com");
        $this->newLine();
        $this->line("   2. Vérifier les règles de firewall:");
        $this->line("      - Autoriser les connexions HTTPS sortantes");
        $this->line("      - Vérifier qu'aucun proxy ne bloque les requêtes");
        $this->newLine();
        $this->line("   3. Vérifier la configuration DNS:");
        $this->line("      nslookup api-checkout.cinetpay.com");
        $this->newLine();
        $this->line("   4. Augmenter temporairement les timeouts si le réseau est lent:");
        $this->line("      Modifier CinetPayService.php: timeout(60) au lieu de timeout(45)");
        $this->newLine();
        $this->line("   5. Vérifier les logs détaillés:");
        $this->line("      tail -f storage/logs/laravel.log | grep -i cinetpay");
    }
}

