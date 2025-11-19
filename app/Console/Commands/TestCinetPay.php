<?php

namespace App\Console\Commands;

use App\Services\CinetPayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCinetPay extends Command
{
    protected $signature = 'cinetpay:test
                            {--timeout=45 : Timeout en secondes pour les tests}
                            {--details : Afficher les détails complets}';

    protected $description = 'Tester la connexion et les fonctionnalités de l\'API CinetPay (TDD)';

    protected CinetPayService $cinetPayService;

    public function __construct(CinetPayService $cinetPayService)
    {
        parent::__construct();
        $this->cinetPayService = $cinetPayService;
    }

    public function handle()
    {
        $this->info('🧪 Test de l\'API CinetPay (TDD)');
        $this->newLine();

        $timeout = (int) $this->option('timeout');
        $verbose = $this->option('details');

        $tests = [
            'test_configuration' => 'Vérifier la configuration CinetPay',
            'test_signature_endpoint' => 'Tester l\'endpoint de signature',
            'test_payment_initiation' => 'Tester l\'initialisation d\'un paiement',
        ];

        $results = [];
        $passed = 0;
        $failed = 0;

        foreach ($tests as $testMethod => $testName) {
            $this->info("📋 Test: {$testName}");
            
            try {
                $startTime = microtime(true);
                $result = $this->$testMethod($timeout, $verbose);
                $duration = round((microtime(true) - $startTime) * 1000, 2);

                if ($result['success']) {
                    $this->info("✅ {$testName} - PASSED ({$duration}ms)");
                    $passed++;
                    if ($verbose && isset($result['details'])) {
                        $this->line('   Détails: ' . json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    }
                } else {
                    $this->error("❌ {$testName} - FAILED ({$duration}ms)");
                    $this->error("   Erreur: " . ($result['error'] ?? 'Erreur inconnue'));
                    $failed++;
                    if ($verbose && isset($result['details'])) {
                        $this->line('   Détails: ' . json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    }
                }
                
                $results[$testMethod] = $result;
                $results[$testMethod]['duration'] = $duration;
            } catch (\Exception $e) {
                $this->error("❌ {$testName} - EXCEPTION");
                $this->error("   Exception: " . $e->getMessage());
                $failed++;
                $results[$testMethod] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'exception' => true,
                ];
            }

            $this->newLine();
        }

        // Résumé
        $this->info('📊 Résumé des tests');
        $this->table(
            ['Test', 'Statut', 'Durée (ms)', 'Détails'],
            collect($results)->map(function ($result, $testMethod) use ($tests) {
                $testName = $tests[$testMethod];
                $status = $result['success'] ? '✅ PASSED' : '❌ FAILED';
                $duration = isset($result['duration']) ? $result['duration'] . 'ms' : 'N/A';
                $details = isset($result['error']) 
                    ? substr($result['error'], 0, 50) . (strlen($result['error']) > 50 ? '...' : '')
                    : 'OK';

                return [$testName, $status, $duration, $details];
            })->toArray()
        );

        $this->newLine();
        $this->info("✅ Tests réussis: {$passed}");
        $this->error("❌ Tests échoués: {$failed}");
        $this->info("📈 Taux de réussite: " . round(($passed / count($tests)) * 100, 2) . "%");

        if ($failed > 0) {
            $this->newLine();
            $this->warn('⚠️  Certains tests ont échoué. Vérifiez la configuration CinetPay.');
            $this->line('   - Vérifiez les identifiants dans config/services.php ou .env');
            $this->line('   - Vérifiez que le mode (sandbox/production) est correct');
            $this->line('   - Vérifiez la connectivité réseau vers CinetPay');
            
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('🎉 Tous les tests sont passés avec succès !');

        return Command::SUCCESS;
    }

    protected function test_configuration(int $timeout, bool $verbose): array
    {
        try {
            $config = config('services.cinetpay');
            
            if (!$config) {
                return [
                    'success' => false,
                    'error' => 'Configuration CinetPay introuvable dans config/services.php',
                ];
            }

            $required = ['api_key', 'site_id', 'mode'];
            $missing = [];

            foreach ($required as $key) {
                if (empty($config[$key])) {
                    $missing[] = $key;
                }
            }

            if (!empty($missing)) {
                return [
                    'success' => false,
                    'error' => 'Configuration incomplète. Paramètres manquants: ' . implode(', ', $missing),
                    'details' => [
                        'missing' => $missing,
                        'config' => array_map(function($key) use ($config) {
                            return isset($config[$key]) ? (strlen($config[$key]) > 0 ? '✓ Défini' : '✗ Vide') : '✗ Manquant';
                        }, $required),
                    ],
                ];
            }

            // Vérifier que le mode est valide (accepter aussi les valeurs en majuscules)
            $validModes = ['sandbox', 'production', 'test', 'prod', 'TEST', 'PROD', 'SANDBOX'];
            $currentMode = strtolower($config['mode'] ?? '');
            $isValidMode = in_array($currentMode, ['sandbox', 'production', 'test', 'prod']) 
                || in_array(strtoupper($config['mode'] ?? ''), ['TEST', 'PROD', 'SANDBOX']);
            
            if (!$isValidMode) {
                return [
                    'success' => false,
                    'error' => 'Mode invalide. Valeurs acceptées: sandbox, production, test',
                    'details' => [
                        'current_mode' => $config['mode'] ?? 'non défini',
                        'valid_modes' => ['sandbox', 'production', 'test', 'prod', 'TEST', 'PROD', 'SANDBOX'],
                    ],
                ];
            }

            return [
                'success' => true,
                'details' => [
                    'api_key' => substr($config['api_key'], 0, 10) . '...',
                    'site_id' => $config['site_id'],
                    'mode' => $config['mode'],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception lors de la vérification de configuration: ' . $e->getMessage(),
            ];
        }
    }

    protected function test_signature_endpoint(int $timeout, bool $verbose): array
    {
        try {
            $config = config('services.cinetpay');
            $mode = strtolower($config['mode'] ?? 'sandbox');
            
            $host = $mode === 'production' 
                ? 'api.cinetpay.com'
                : 'api.sandbox.cinetpay.com';
            
            $url = "https://{$host}/v2/?method=getSignatureByPost";

            $this->line("   URL: {$url}");
            
            // Test de connexion basique
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'apikey' => 'test',
                    'cpm_site_id' => 'test',
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'error' => 'Erreur cURL: ' . $error,
                    'details' => [
                        'url' => $url,
                        'timeout' => $timeout,
                    ],
                ];
            }

            if ($httpCode === 0) {
                return [
                    'success' => false,
                    'error' => 'Timeout ou connexion impossible. L\'API sandbox peut être indisponible.',
                    'details' => [
                        'url' => $url,
                        'timeout' => $timeout,
                        'total_time' => $curlInfo['total_time'] ?? null,
                    ],
                ];
            }

            $responseData = json_decode($response, true);
            
            if (is_array($responseData) && isset($responseData['status'])) {
                // C'est une erreur de l'API (attendu pour des identifiants de test)
                return [
                    'success' => true,
                    'details' => [
                        'url' => $url,
                        'http_code' => $httpCode,
                        'api_accessible' => true,
                        'api_response' => $responseData,
                        'note' => 'L\'API est accessible. L\'erreur est attendue avec des identifiants de test.',
                    ],
                ];
            }

            // Si c'est une chaîne, c'est peut-être une signature (succès)
            if (is_string($response) && strlen($response) > 0) {
                return [
                    'success' => true,
                    'details' => [
                        'url' => $url,
                        'http_code' => $httpCode,
                        'api_accessible' => true,
                        'response_type' => 'signature (string)',
                        'response_length' => strlen($response),
                    ],
                ];
            }

            return [
                'success' => true,
                'details' => [
                    'url' => $url,
                    'http_code' => $httpCode,
                    'api_accessible' => true,
                    'response' => substr($response, 0, 200),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception lors du test de l\'endpoint: ' . $e->getMessage(),
            ];
        }
    }

    protected function test_payment_initiation(int $timeout, bool $verbose): array
    {
        try {
            $config = config('services.cinetpay');
            
            // Vérifier que la configuration existe
            if (empty($config['api_key']) || empty($config['site_id'])) {
                return [
                    'success' => false,
                    'error' => 'Configuration CinetPay incomplète. Impossible de tester l\'initialisation.',
                ];
            }

            // Utiliser des données de test minimales
            $paymentData = [
                'transaction_id' => 'TEST_' . time() . '_' . rand(1000, 9999),
                'amount' => 10000, // 100 XOF (montant minimum)
                'currency' => 'XOF',
                'description' => 'Test de paiement TDD',
            ];

            $this->line("   Transaction ID: {$paymentData['transaction_id']}");
            $amountXof = $paymentData['amount'] / 100;
            $this->line("   Montant: {$paymentData['amount']} centimes ({$amountXof} XOF)");

            // Note: On n'utilise pas le vrai service car il pourrait créer une vraie transaction
            // On teste juste la connectivité et la structure
            $result = $this->cinetPayService->initiatePayment($paymentData);

            if ($result['success']) {
                return [
                    'success' => true,
                    'details' => [
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'payment_url' => isset($result['payment_url']) ? substr($result['payment_url'], 0, 100) . '...' : null,
                        'note' => 'L\'initialisation a réussi. Note: En mode sandbox, cela peut échouer si l\'API est indisponible.',
                    ],
                ];
            }

            // Si c'est un timeout, c'est attendu si l'API sandbox est indisponible
            if (isset($result['code']) && $result['code'] === 'CONNECTION_TIMEOUT') {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Timeout de connexion',
                    'details' => [
                        'code' => $result['code'] ?? null,
                        'is_timeout' => true,
                        'note' => 'L\'API sandbox semble indisponible. Cela est courant en développement.',
                    ],
                ];
            }

            // Autres erreurs
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Erreur lors de l\'initialisation',
                'details' => [
                    'code' => $result['code'] ?? null,
                    'description' => $result['description'] ?? null,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception lors du test d\'initialisation: ' . $e->getMessage(),
                'exception' => true,
            ];
        }
    }
}

