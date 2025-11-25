<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

// L'ancien SDK n'est plus utilisé pour les opérations principales,
// on le retire pour alléger le code.
// if (!class_exists('CinetPay')) {
//    require_once base_path('cinetpay-php-sdk-master/src/cinetpay.php');
// }

class CinetPayService
{
    protected string $apiKey;
    protected string $siteId;
    protected string $notifyUrl;
    protected string $returnUrl;
    protected string $cancelUrl;
    protected string $mode;
    protected string $apiUrl; // URL de base pour l'API v2

    public function __construct()
    {
        try {
            // Lire depuis la base de données avec fallback sur .env
            $config = config('services.cinetpay', []);
            
            // On ne définit que les credentials ici
            $this->apiKey = Setting::get('cinetpay.api_key', $config['api_key'] ?? env('CINETPAY_API_KEY', ''));
            $this->siteId = Setting::get('cinetpay.site_id', $config['site_id'] ?? env('CINETPAY_SITE_ID', ''));
            
            // URLs avec priorité : base de données > config > .env > génération automatique
            $notifyUrl = Setting::get('cinetpay.notify_url', $config['notify_url'] ?? env('CINETPAY_NOTIFY_URL'));
            $returnUrl = Setting::get('cinetpay.return_url', $config['return_url'] ?? env('CINETPAY_RETURN_URL'));
            $cancelUrl = Setting::get('cinetpay.cancel_url', $config['cancel_url'] ?? env('CINETPAY_CANCEL_URL'));
            
            // Utiliser url() helper pour générer les URLs absolues si non définies
            // Cela garantit que les URLs fonctionnent même si l'IP/port change
            $this->notifyUrl = $notifyUrl ?? url('/api/payments/cinetpay/webhook');
            $this->returnUrl = $returnUrl ?? url('/api/payments/return');
            $this->cancelUrl = $cancelUrl ?? url('/api/payments/cancel');
            
            // Mode avec priorité : base de données > config > .env > sandbox par défaut
            $mode = Setting::get('cinetpay.mode', $config['mode'] ?? env('CINETPAY_MODE', 'sandbox'));
            
            // Nettoyer le mode pour gérer les cas où il est collé avec d'autres variables
            // Extraire uniquement le mode valide (sandbox, production, test, prod)
            $this->mode = strtolower(trim((string)$mode));
            if (strpos($this->mode, 'production') !== false || strpos($this->mode, 'prod') !== false) {
                $this->mode = 'production';
            } else {
                $this->mode = 'sandbox';
            }

            // Centraliser l'URL de l'API, en s'assurant qu'elle ne contient pas le chemin
            $this->apiUrl = rtrim(config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com'), '/');

            // On supprime toute l'initialisation de l'ancien SDK
        } catch (\Exception $e) {
            // En cas d'erreur lors de l'initialisation (table settings manquante, etc.)
            // Utiliser les valeurs de .env uniquement
            Log::debug('CinetPayService initialization fallback to .env', [
                'error' => $e->getMessage(),
            ]);

            $config = config('services.cinetpay', []);
            $this->apiKey = $config['api_key'] ?? env('CINETPAY_API_KEY', '');
            $this->siteId = $config['site_id'] ?? env('CINETPAY_SITE_ID', '');
            $this->notifyUrl = $config['notify_url'] ?? env('CINETPAY_NOTIFY_URL', url('/api/payments/cinetpay/webhook'));
            $this->returnUrl = $config['return_url'] ?? env('CINETPAY_RETURN_URL', url('/api/payments/return'));
            $this->cancelUrl = $config['cancel_url'] ?? env('CINETPAY_CANCEL_URL', url('/api/payments/cancel'));
            $this->mode = $config['mode'] ?? env('CINETPAY_MODE', 'sandbox');
            $this->apiUrl = rtrim(config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com'), '/');
        }
    }

    /**
     * Initialiser un paiement selon la méthode du SDK officiel CinetPay
     *
     * @param array $paymentData
     * @return array
     */
    public function initiatePayment(array $paymentData): array
    {
        // Valider que la configuration de base est présente
        if (empty($this->apiKey) || empty($this->siteId) || $this->apiKey === 'temp_key' || $this->siteId === 'temp_site') {
            Log::error('CinetPay n\'est pas configuré.', [
                'api_key_set' => !empty($this->apiKey) && $this->apiKey !== 'temp_key',
                'site_id_set' => !empty($this->siteId) && $this->siteId !== 'temp_site',
            ]);
            return [
                'success' => false,
                'error' => 'CinetPay n\'est pas configuré. Veuillez vérifier l\'API Key et le Site ID dans l\'administration.',
                'code' => 'SDK_NOT_INITIALIZED',
                'is_timeout' => false,
            ];
        }
        
        // CinetPay V2 attend le montant en entier (pas en centimes)
        $amountInCents = $paymentData['amount'];
        $amount = (int) round($amountInCents / 100);

        // CinetPay requiert un montant minimum de 100 XOF
        $minAmountXOF = 100;
        if ($amount < $minAmountXOF) {
            return [
                'success' => false,
                'error' => "Le montant minimum requis est de {$minAmountXOF} XOF. Le montant actuel est de {$amount} XOF.",
                'code' => 'AMOUNT_TOO_LOW',
                'is_timeout' => false,
            ];
        }
        
        $transactionId = $paymentData['transaction_id'] ?? $this->generateTransactionId();

        $payload = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $paymentData['currency'] ?? 'XOF',
            'description' => $paymentData['description'] ?? 'Paiement de réservation',
            'notify_url' => $this->notifyUrl,
            'return_url' => $this->returnUrl,
            'channels' => 'ALL',
            // Ajouter les détails du client si disponibles
            'customer_id' => $paymentData['customer_id'] ?? null,
            'customer_name' => $paymentData['customer_name'] ?? 'N/A',
            'customer_surname' => $paymentData['customer_surname'] ?? 'N/A',
            'customer_email' => $paymentData['customer_email'] ?? null,
            'customer_phone_number' => $paymentData['customer_phone_number'] ?? null,
        ];

        // Construire l'URL de manière fiable et défensive
        $baseUrl = config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com');
        // Remplacer toute occurrence de 'v2/payment' pour éviter les doublons, puis ajouter la bonne fin
        $cleanedBaseUrl = rtrim(str_replace('/v2/payment', '', $baseUrl), '/');
        $url = $cleanedBaseUrl . '/v2/payment';

        Log::debug('CinetPay - FINAL CHECK', ['url' => $url, 'payload' => $payload]);

        try {
            $response = Http::asJson()->timeout(45)->post($url, $payload);
            
            // Log de la réponse brute pour déboguer
            Log::info('CinetPay raw response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);
            
            if ($response->failed()) {
                $errorBody = $response->json() ?? ['message' => $response->body()];
                Log::error('Erreur API CinetPay (initiatePayment)', [
                    'status' => $response->status(),
                    'response' => $errorBody,
                    'url' => $url,
                ]);
<<<<<<< HEAD
                
                // Utiliser un timeout PHP pour limiter le temps d'attente
                $startTime = microtime(true);
                $maxExecutionTime = 10; // Maximum 10 secondes pour obtenir la signature
                
                // Appeler getSignature avec gestion de timeout
                $signature = null;
                $oldTimeout = ini_get('default_socket_timeout');
                ini_set('default_socket_timeout', $maxExecutionTime);
                
                try {
                    $signature = $this->cinetPay->getSignature();
                } finally {
                    ini_set('default_socket_timeout', $oldTimeout);
                }
                
                $executionTime = microtime(true) - $startTime;
                
                // Vérifier si le timeout a été dépassé
                if ($executionTime >= $maxExecutionTime) {
                    throw new \Exception('Timeout: L\'appel à getSignature() a pris plus de ' . $maxExecutionTime . ' secondes');
                }
                
                Log::info('CinetPay: Signature obtenue avec succès', [
                    'transaction_id' => $transactionId,
                    'signature_length' => strlen($signature),
                ]);
            } catch (\Exception $e) {
                // Si l'appel prend trop de temps ou échoue, logger et retourner une erreur
                $errorMessage = $e->getMessage();
                $isTimeout = false;
                
                Log::error('CinetPay getSignature error', [
                    'error' => $errorMessage,
                    'transaction_id' => $transactionId,
                    'error_type' => get_class($e),
                    'trace' => substr($e->getTraceAsString(), 0, 500), // Limiter la taille du trace
                ]);
                
                // Vérifier si c'est un timeout
                if (stripos($errorMessage, 'timeout') !== false || 
                    stripos($errorMessage, 'timed out') !== false ||
                    stripos($errorMessage, 'Maximum execution time') !== false ||
                    stripos($errorMessage, 'Connection timed out') !== false) {
                    $isTimeout = true;
                }
                
                // Vérifier si c'est une erreur de configuration
                if (stripos($errorMessage, 'indisponible') !== false ||
                    stripos($errorMessage, 'temporairement') !== false ||
                    stripos($errorMessage, 'probleme est survenu') !== false) {
                    return [
                        'success' => false,
                        'error' => 'L\'API CinetPay est temporairement indisponible. Veuillez réessayer plus tard.',
                        'code' => 'API_UNAVAILABLE',
                        'is_timeout' => false,
                        'description' => $errorMessage,
                    ];
                }
                
                if ($isTimeout) {
                    return [
                        'success' => false,
                        'error' => 'L\'API CinetPay ne répond pas dans les temps. Veuillez réessayer plus tard.',
                        'code' => 'TIMEOUT',
                        'is_timeout' => true,
                        'description' => $errorMessage,
                    ];
                }
                
                // Autre erreur
                return [
                    'success' => false,
                    'error' => 'Erreur lors de l\'obtention de la signature CinetPay: ' . $errorMessage,
                    'code' => 'SIGNATURE_ERROR',
                    'is_timeout' => false,
                    'description' => $errorMessage,
                ];
            }

            // Construire l'URL de paiement avec tous les paramètres
            $cashDeskUrl = $this->getCashDeskUrl();
            $paymentUrl = $this->buildPaymentUrlFromSdk($cashDeskUrl, $signature);

            Log::info('CinetPay payment initiated via SDK', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => $paymentData['currency'] ?? 'XOF',
            ]);

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_url' => $paymentUrl,
                'data' => [
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'currency' => $paymentData['currency'] ?? 'XOF',
                ],
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorCode = 'SDK_ERROR';
            $errorDescription = null;
            $isTimeout = false;

            // Détecter les erreurs de timeout
            if (stripos($errorMessage, 'timeout') !== false || 
                stripos($errorMessage, 'Connection timed out') !== false ||
                stripos($errorMessage, 'timed out after') !== false) {
                $isTimeout = true;
                $errorCode = 'CONNECTION_TIMEOUT';
                $errorDescription = 'TIMEOUT';
                
                // Message d'erreur personnalisé pour les timeouts
                if ($this->mode === 'sandbox' || $this->mode === 'test') {
                    $errorMessage = 'L\'API sandbox de CinetPay est temporairement indisponible ou ne répond pas. Le timeout de connexion (10 secondes) a été dépassé. Veuillez réessayer plus tard ou contacter le support si le problème persiste.';
                } else {
                    $errorMessage = 'Timeout de connexion à l\'API CinetPay. Le serveur ne répond pas dans les délais impartis. Veuillez réessayer plus tard.';
                }
            } else {
                // Extraire le code d'erreur et le message depuis l'exception CinetPay
                // Format: "Une erreur est survenue, Code: 641, Message: ERROR_AMOUNT_TOO_LOW"
                if (preg_match('/Code:\s*(\d+)/', $errorMessage, $codeMatches)) {
                    $errorCode = $codeMatches[1];
                }
                if (preg_match('/Message:\s*(.+?)(?:$|,)/', $errorMessage, $messageMatches)) {
                    $errorDescription = trim($messageMatches[1]);
                }

                // Message d'erreur personnalisé pour l'erreur 641
                if ($errorCode === '641' || $errorDescription === 'ERROR_AMOUNT_TOO_LOW') {
                    $errorMessage = "Le montant minimum requis est de {$minAmountXOF} XOF (soit {$minAmountCents} centimes). Le montant actuel est de {$amount} XOF.";
                }
            }

            Log::error('CinetPay payment initiation error via SDK', [
                'error' => $errorMessage,
                'code' => $errorCode,
                'description' => $errorDescription,
                'is_timeout' => $isTimeout,
                'transaction_id' => $transactionId,
                'amount_in_cents' => $amountInCents,
                'amount_in_xof' => $amount,
                'mode' => $this->mode,
                'original_exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de l\'initialisation du paiement: ' . $errorMessage,
                'code' => $errorCode,
                'description' => $errorDescription,
                'is_timeout' => $isTimeout,
            ];
        }
    }

    /**
     * Construire l'URL de paiement à partir des données du SDK
     */
    protected function buildPaymentUrlFromSdk(string $baseUrl, string $signature): string
    {
        // Récupérer les données configurées dans le SDK
        $data = $this->cinetPay->getPayDataArray();
        $data['signature'] = $signature;

        return $baseUrl . '?' . http_build_query($data);
    }

    /**
     * Obtenir la signature depuis CinetPay
     */
    protected function getSignature(array $data): array
    {
        $url = $this->getSignatureUrl();

        try {
            // CinetPay attend les données en format application/x-www-form-urlencoded
            // Réduire le timeout à 10 secondes pour éviter les attentes trop longues
            // Désactiver la vérification SSL en développement local pour éviter les erreurs de certificat
            $response = Http::timeout(10)
                ->connectTimeout(5) // Timeout de connexion à 5 secondes
                ->withoutVerifying() // Désactiver la vérification SSL
                ->asForm()
                ->post($url, $data);

            $responseText = $response->body();

            Log::info('CinetPay signature response', [
                'status' => $response->status(),
                'response_length' => strlen($responseText),
                'url' => $url,
            ]);

            // La réponse est une chaîne de caractères (la signature) ou un JSON avec erreur
            $decoded = json_decode($responseText, true);

            if (is_array($decoded)) {
                // C'est une erreur
                Log::error('CinetPay signature error response', [
                    'error' => $decoded,
                    'url' => $url,
                ]);
                return [
                    'success' => false,
                    'error' => $decoded['status']['message'] ?? 'Erreur lors de l\'obtention de la signature',
                    'code' => $decoded['status']['code'] ?? 'UNKNOWN',
                ];
            }

            // C'est la signature (chaîne de caractères)
            return [
                'success' => true,
                'signature' => trim($responseText),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('CinetPay connection timeout', [
                'error' => $e->getMessage(),
                'url' => $url,
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => 'Timeout de connexion à CinetPay. L\'API sandbox semble indisponible. Veuillez réessayer plus tard ou utiliser le mode production.',
                'code' => 'CONNECTION_TIMEOUT',
            ];
        } catch (\Exception $e) {
            Log::error('CinetPay signature error', [
                'error' => $e->getMessage(),
                'url' => $url,
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => 'Erreur de connexion à CinetPay: ' . $e->getMessage(),
                'code' => 'UNKNOWN',
            ];
        }
    }

    /**
     * Construire l'URL de paiement avec tous les paramètres
     */
    protected function buildPaymentUrl(string $baseUrl, array $data, string $signature): string
    {
        $params = [
            'apikey' => $data['apikey'],
            'cpm_site_id' => $data['cpm_site_id'],
            'cpm_currency' => $data['cpm_currency'],
            'cpm_page_action' => $data['cpm_page_action'],
            'cpm_payment_config' => $data['cpm_payment_config'],
            'cpm_version' => $data['cpm_version'],
            'cpm_language' => $data['cpm_language'],
            'cpm_trans_date' => $data['cpm_trans_date'],
            'cpm_trans_id' => $data['cpm_trans_id'],
            'cpm_designation' => $data['cpm_designation'],
            'cpm_amount' => $data['cpm_amount'],
            'signature' => $signature,
        ];

        if (!empty($data['cpm_custom'])) {
            $params['cpm_custom'] = $data['cpm_custom'];
        }

        if (!empty($this->notifyUrl)) {
            $params['notify_url'] = $this->notifyUrl;
        }

        if (!empty($this->returnUrl)) {
            $params['return_url'] = $this->returnUrl;
        }

        if (!empty($this->cancelUrl)) {
            $params['cancel_url'] = $this->cancelUrl;
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Obtenir l'URL du cash desk CinetPay
     */
    protected function getCashDeskUrl(): string
    {
        // CinetPay utilise toujours HTTPS
        $host = $this->mode === 'production'
            ? 'secure.cinetpay.com'
            : 'secure.sandbox.cinetpay.com';

        return 'https://' . $host;
    }

    /**
     * Obtenir l'URL pour obtenir la signature
     */
    protected function getSignatureUrl(): string
    {
        // CinetPay utilise toujours HTTPS
        $host = $this->mode === 'production'
            ? 'api.cinetpay.com'
            : 'api.sandbox.cinetpay.com';

        $version = strtolower($this->version);
        return 'https://' . $host . '/' . $version . '/?method=getSignatureByPost';
    }

    /**
     * Vérifier le statut d'un paiement via le SDK officiel
     *
     * @param string $transactionId
     * @return array
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        try {
            // Utiliser le SDK officiel pour vérifier le statut
            $this->cinetPay->setTransId($transactionId);
            $this->cinetPay->getPayStatus();

            // Récupérer les données de la transaction
            $transaction = [
                'cpm_site_id' => $this->cinetPay->_cpm_site_id,
                'signature' => $this->cinetPay->_signature,
                'cpm_amount' => $this->cinetPay->_cpm_amount,
                'cpm_trans_id' => $this->cinetPay->_cpm_trans_id,
                'cpm_currency' => $this->cinetPay->_cpm_currency,
                'cpm_payid' => $this->cinetPay->_cpm_payid,
                'cpm_payment_date' => $this->cinetPay->_cpm_payment_date,
                'cpm_payment_time' => $this->cinetPay->_cpm_payment_time,
                'cpm_error_message' => $this->cinetPay->_cpm_error_message,
                'payment_method' => $this->cinetPay->_payment_method,
                'cpm_result' => $this->cinetPay->_cpm_result,
                'cpm_trans_status' => $this->cinetPay->_cpm_trans_status,
                'cpm_designation' => $this->cinetPay->_cpm_designation,
                'buyer_name' => $this->cinetPay->_buyer_name,
            ];

            // Vérifier que le site_id correspond
            if ($transaction['cpm_site_id'] != $this->siteId) {
                return [
                    'success' => false,
                    'error' => 'Site ID ne correspond pas',
                ];
            }

            return [
                'success' => true,
                'data' => $transaction,
                'status' => $transaction['cpm_result'] === '00' ? 'ACCEPTED' : 'REFUSED',
                'cpm_result' => $transaction['cpm_result'],
                'cpm_trans_status' => $transaction['cpm_trans_status'],
                'cpm_amount' => $transaction['cpm_amount'],
                'payment_method' => $transaction['payment_method'],
                'buyer_name' => $transaction['buyer_name'],
            ];
        } catch (\Exception $e) {
            Log::error('CinetPay payment status check error via SDK', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de la vérification du paiement: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtenir l'URL pour vérifier le statut du paiement
     */
    protected function getCheckPayStatusUrl(): string
    {
        // CinetPay utilise toujours HTTPS
        $host = $this->mode === 'production'
            ? 'api.cinetpay.com'
            : 'api.sandbox.cinetpay.com';

        $version = strtolower($this->version);
        return 'https://' . $host . '/' . $version . '/?method=checkPayStatus';
    }

    /**
     * Générer un ID de transaction unique
     *
     * @return string
     */
    protected function generateTransactionId(): string
    {
        $timestamp = time();
        $parts = explode(' ', microtime());
        $id = ($timestamp + $parts[0] - strtotime('today 00:00')) * 10;
        $id = sprintf('%06d', $id) . mt_rand(100, 9999);

        return 'NETLAB_' . $id;
    }

    /**
     * Vérifier l'état de santé de l'API CinetPay
     *
     * @return array
     */
    public function checkHealth(): array
    {
        $health = [
            'status' => 'unknown',
            'timestamp' => now()->toIso8601String(),
            'configuration' => [],
            'connectivity' => [],
            'api_status' => [],
            'overall_health' => 'unknown',
        ];

        // 1. Vérifier la configuration (depuis la base de données avec fallback)
        $apiKey = Setting::get('cinetpay.api_key', config('services.cinetpay.api_key') ?? env('CINETPAY_API_KEY', ''));
        $siteId = Setting::get('cinetpay.site_id', config('services.cinetpay.site_id') ?? env('CINETPAY_SITE_ID', ''));
        $mode = Setting::get('cinetpay.mode', config('services.cinetpay.mode') ?? env('CINETPAY_MODE', 'sandbox'));
        $notifyUrl = Setting::get('cinetpay.notify_url', config('services.cinetpay.notify_url') ?? env('CINETPAY_NOTIFY_URL', url('/api/payments/cinetpay/webhook')));
        $returnUrl = Setting::get('cinetpay.return_url', config('services.cinetpay.return_url') ?? env('CINETPAY_RETURN_URL', url('/api/payments/return')));
        $cancelUrl = Setting::get('cinetpay.cancel_url', config('services.cinetpay.cancel_url') ?? env('CINETPAY_CANCEL_URL', url('/api/payments/cancel')));
        
        $health['configuration'] = [
            'api_key' => !empty($apiKey) ? '✓ Défini (' . substr($apiKey, 0, 8) . '...)' : '✗ Manquant',
            'site_id' => !empty($siteId) ? '✓ Défini (' . $siteId . ')' : '✗ Manquant',
            'mode' => $mode ?? 'non défini',
            'notify_url' => $notifyUrl ?? url('/api/payments/cinetpay/webhook'),
            'return_url' => $returnUrl ?? url('/api/payments/return'),
            'cancel_url' => $cancelUrl ?? url('/api/payments/cancel'),
        ];

        $configValid = !empty($apiKey) && !empty($siteId) && !empty($mode);

        // 2. Vérifier la connectivité réseau
        $signatureUrl = $this->getSignatureUrl();
        $connectivityStart = microtime(true);
        
        try {
            $response = Http::timeout(10)->asForm()->post($signatureUrl, [
                'apikey' => $this->apiKey,
                'cpm_site_id' => $this->siteId,
            ]);
            
            $connectivityDuration = round((microtime(true) - $connectivityStart) * 1000, 2);
            
            $health['connectivity'] = [
                'status' => $response->successful() ? 'reachable' : 'unreachable',
                'response_time_ms' => $connectivityDuration,
                'http_status' => $response->status(),
                'url' => $signatureUrl,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $connectivityDuration = round((microtime(true) - $connectivityStart) * 1000, 2);
            $health['connectivity'] = [
                'status' => 'timeout',
                'response_time_ms' => $connectivityDuration,
                'error' => 'Timeout de connexion (>10s)',
                'url' => $signatureUrl,
            ];
        } catch (\Exception $e) {
            $connectivityDuration = round((microtime(true) - $connectivityStart) * 1000, 2);
            $health['connectivity'] = [
                'status' => 'error',
                'response_time_ms' => $connectivityDuration,
                'error' => $e->getMessage(),
                'url' => $signatureUrl,
            ];
        }

        // 3. Tester l'initialisation d'un paiement (sans créer de transaction réelle)
        $apiTestStart = microtime(true);
        try {
            // Test avec un montant minimal (100 XOF = 10000 centimes)
            $testPaymentData = [
                'amount' => 10000, // 100 XOF en centimes
                'currency' => 'XOF',
                'description' => 'Test de santé API - ' . now()->toDateTimeString(),
                'transaction_id' => 'HEALTH_CHECK_' . time(),
            ];

            $testResult = $this->initiatePayment($testPaymentData);
            $apiTestDuration = round((microtime(true) - $apiTestStart) * 1000, 2);

            $health['api_status'] = [
                'status' => $testResult['success'] ? 'operational' : 'error',
                'response_time_ms' => $apiTestDuration,
                'can_initiate_payment' => $testResult['success'],
                'error' => $testResult['error'] ?? null,
                'code' => $testResult['code'] ?? null,
            ];
        } catch (\Exception $e) {
            $apiTestDuration = round((microtime(true) - $apiTestStart) * 1000, 2);
            $health['api_status'] = [
                'status' => 'error',
                'response_time_ms' => $apiTestDuration,
                'can_initiate_payment' => false,
                'error' => $e->getMessage(),
            ];
        }

        // 4. Déterminer l'état de santé global
        $issues = [];
        
        if (!$configValid) {
            $issues[] = 'Configuration incomplète';
        }
        
        if ($health['connectivity']['status'] === 'timeout' || $health['connectivity']['status'] === 'error') {
            $issues[] = 'API non accessible';
        }
        
        if ($health['api_status']['status'] === 'error' || !$health['api_status']['can_initiate_payment']) {
            $issues[] = 'Impossible d\'initialiser un paiement';
        }

        if (empty($issues)) {
            $health['overall_health'] = 'healthy';
            $health['status'] = 'operational';
        } elseif (count($issues) === 1 && $issues[0] === 'Configuration incomplète') {
            $health['overall_health'] = 'degraded';
            $health['status'] = 'misconfigured';
        } else {
            $health['overall_health'] = 'unhealthy';
            $health['status'] = 'down';
        }

        $health['issues'] = $issues;
        $health['summary'] = [
            'config_valid' => $configValid,
            'api_reachable' => in_array($health['connectivity']['status'], ['reachable']),
            'payment_working' => $health['api_status']['can_initiate_payment'] ?? false,
        ];

        return $health;
    }
}
            
            // Log détaillé de la structure de la réponse
            Log::info('CinetPay parsed response', [
                'full_data' => $data,
                'has_data_key' => isset($data['data']),
                'code' => $data['code'] ?? null,
                'message' => $data['message'] ?? null,
            ]);
            
            Log::info('CinetPay API response structure', [
                'full_response' => $data,
                'has_data_key' => isset($data['data']),
                'data_structure' => $data['data'] ?? null,
            ]);

            // Chercher payment_url dans plusieurs emplacements possibles
            // Vérifier d'abord si data existe et est un tableau
            $dataData = is_array($data['data'] ?? null) ? $data['data'] : [];
            $paymentUrl = $dataData['payment_url'] ?? 
                         $dataData['paymentUrl'] ?? 
                         $data['payment_url'] ?? 
                         $data['paymentUrl'] ??
                         ($dataData['checkout_url'] ?? null) ??
                         ($data['checkout_url'] ?? null) ??
                         null;
            
            // Log détaillé pour déboguer
            Log::info('CinetPay payment_url search', [
                'data_data_payment_url' => $dataData['payment_url'] ?? 'NOT_SET',
                'data_data_paymentUrl' => $dataData['paymentUrl'] ?? 'NOT_SET',
                'data_payment_url' => $data['payment_url'] ?? 'NOT_SET',
                'data_paymentUrl' => $data['paymentUrl'] ?? 'NOT_SET',
                'found_payment_url' => $paymentUrl,
                'has_data' => isset($data['data']),
                'data_is_array' => is_array($data['data'] ?? null),
                'data_keys' => !empty($dataData) ? array_keys($dataData) : null,
            ]);
            
            // Vérifier les codes de succès (CinetPay peut retourner '0', '201', ou 'SUCCES')
            $code = $data['code'] ?? null;
            $isSuccessCode = ($code === '0' || $code === '201' || $code === 201 || $code === 'SUCCES');
            
            // Vérifier aussi si le message indique un succès
            $message = strtolower($data['message'] ?? $data['description'] ?? '');
            $isSuccessMessage = strpos($message, 'created') !== false || 
                               strpos($message, 'success') !== false ||
                               strpos($message, 'succes') !== false;
            
            Log::info('CinetPay success check', [
                'code' => $code,
                'isSuccessCode' => $isSuccessCode,
                'message' => $data['message'] ?? null,
                'isSuccessMessage' => $isSuccessMessage,
            ]);
            
            // Si on a un payment_url, c'est un succès (peu importe le code)
            if ($paymentUrl) {
                Log::info('CinetPay payment_url found', [
                    'payment_url' => $paymentUrl,
                    'code' => $code,
                    'message' => $data['message'] ?? null,
                ]);
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'payment_url' => $paymentUrl,
                    'data' => !empty($dataData) ? $dataData : $data,
                ];
            }
            
            // Si c'est un code de succès (201) ou un message de succès mais pas de payment_url
            // C'est un cas anormal, mais on doit quand même retourner les informations
            if ($isSuccessCode || $isSuccessMessage) {
                Log::error('CinetPay retourne un succès (code: ' . $code . ') mais pas de payment_url dans la réponse', [
                    'response' => $data,
                    'code' => $code,
                    'message' => $data['message'] ?? null,
                    'description' => $data['description'] ?? null,
                    'has_data' => isset($data['data']),
                    'data_keys' => isset($data['data']) ? array_keys($data['data']) : null,
                ]);
                // Retourner une erreur mais avec les informations de succès
                return [
                    'success' => false,
                    'error' => $data['message'] ?? $data['description'] ?? 'Transaction créée mais URL de paiement manquante',
                    'code' => $code ?? 'INVALID_RESPONSE',
                    'description' => $data['description'] ?? 'Format de réponse invalide.',
                    'is_timeout' => false,
                ];
            }

            // Si le code n'est pas un succès et pas de payment_url, c'est un échec
            Log::warning('Réponse inattendue ou échec de CinetPay (initiatePayment)', [
                'response' => $data,
            ]);
            return [
                'success' => false,
                'error' => $data['message'] ?? 'La réponse de CinetPay ne contient pas de lien de paiement.',
                'code' => $data['code'] ?? 'INVALID_RESPONSE',
                'description' => $data['description'] ?? 'Format de réponse invalide.',
                'is_timeout' => false,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Timeout de connexion à CinetPay (initiatePayment)', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            return [
                'success' => false,
                'error' => 'Le service de paiement est indisponible pour le moment. Veuillez réessayer plus tard.',
                'code' => 'CONNECTION_TIMEOUT',
                'is_timeout' => true,
            ];
        }
    }

    /**
     * Nouvelle méthode pour obtenir la signature directement depuis l'API
     */
    protected function fetchSignatureFromApi(array $data): array
    {
        $url = $this->getSignatureUrl();

        try {
            // Utiliser le client HTTP de Laravel
            $response = Http::asForm()->timeout(45)->post($url, $data);

            if ($response->failed()) {
                Log::error('CinetPay signature API error response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $url,
                ]);
                return [
                    'success' => false,
                    'error' => 'L\'API CinetPay a retourné une erreur.',
                    'code' => 'API_ERROR_' . $response->status(),
                ];
            }

            // L'API peut retourner du JSON ou une simple chaîne
            $body = $response->body();
            $decoded = json_decode($body, true);

            // Si c'est un JSON avec un code d'erreur
            if (is_array($decoded) && isset($decoded['code'])) {
                 if ($decoded['code'] !== '0') { // '0' est souvent succès
                     Log::error('CinetPay signature API returned an error message', [
                        'response' => $decoded,
                        'url' => $url,
                    ]);
                    return [
                        'success' => false,
                        'error' => $decoded['message'] ?? 'Erreur inconnue de CinetPay.',
                        'code' => $decoded['code'],
                    ];
                 }
                 // Si le code est succès mais la signature est ailleurs dans la réponse
                 if(isset($decoded['data']['signature'])){
                    return [
                        'success' => true,
                        'signature' => $decoded['data']['signature'],
                    ];
                 }
            }
            
            // Si la réponse est la signature directement (chaîne)
            if (is_string($body) && !empty(trim($body))) {
                return [
                    'success' => true,
                    'signature' => trim($body),
                ];
            }

            // Cas inattendu
            Log::warning('CinetPay signature: unexpected response format', [
                'body' => $body,
                'url' => $url,
            ]);
            return ['success' => false, 'error' => 'Format de réponse de signature inattendu.'];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('CinetPay signature connection timeout', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            return [
                'success' => false,
                'error' => 'Timeout de connexion à l\'API CinetPay.', 
                'is_timeout' => true,
            ];
        }
    }

    /**
     * Construire l'URL de paiement à partir des données du SDK
     */
    protected function buildPaymentUrlFromSdk(string $baseUrl, string $signature): string
    {
        // Récupérer les données configurées dans le SDK
        $data = $this->cinetPay->getPayDataArray();
        $data['signature'] = $signature;

        return $baseUrl . '?' . http_build_query($data);
    }

    /**
     * Obtenir la signature depuis CinetPay
     */
    protected function getSignature(array $data): array
    {
        $url = $this->getSignatureUrl();

        try {
            // CinetPay attend les données en format application/x-www-form-urlencoded
            // Réduire le timeout à 10 secondes pour éviter les attentes trop longues
            // Désactiver la vérification SSL en développement local pour éviter les erreurs de certificat
            $response = Http::timeout(10)
                ->connectTimeout(5) // Timeout de connexion à 5 secondes
                ->withoutVerifying() // Désactiver la vérification SSL
                ->asForm()
                ->post($url, $data);

            $responseText = $response->body();

            Log::info('CinetPay signature response', [
                'status' => $response->status(),
                'response_length' => strlen($responseText),
                'url' => $url,
            ]);

            // La réponse est une chaîne de caractères (la signature) ou un JSON avec erreur
            $decoded = json_decode($responseText, true);

            if (is_array($decoded)) {
                // C'est une erreur
                Log::error('CinetPay signature error response', [
                    'error' => $decoded,
                    'url' => $url,
                ]);
                return [
                    'success' => false,
                    'error' => $decoded['status']['message'] ?? 'Erreur lors de l\'obtention de la signature',
                    'code' => $decoded['status']['code'] ?? 'UNKNOWN',
                ];
            }

            // C'est la signature (chaîne de caractères)
            return [
                'success' => true,
                'signature' => trim($responseText),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('CinetPay connection timeout', [
                'error' => $e->getMessage(),
                'url' => $url,
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => 'Timeout de connexion à CinetPay. L\'API sandbox semble indisponible. Veuillez réessayer plus tard ou utiliser le mode production.',
                'code' => 'CONNECTION_TIMEOUT',
            ];
        } catch (\Exception $e) {
            Log::error('CinetPay signature error', [
                'error' => $e->getMessage(),
                'url' => $url,
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => 'Erreur de connexion à CinetPay: ' . $e->getMessage(),
                'code' => 'UNKNOWN',
            ];
        }
    }

    /**
     * Construire l'URL de paiement avec tous les paramètres
     */
    protected function buildPaymentUrl(string $baseUrl, array $data, string $signature): string
    {
        $params = [
            'apikey' => $data['apikey'],
            'cpm_site_id' => $data['cpm_site_id'],
            'cpm_currency' => $data['cpm_currency'],
            'cpm_page_action' => $data['cpm_page_action'],
            'cpm_payment_config' => $data['cpm_payment_config'],
            'cpm_version' => $data['cpm_version'],
            'cpm_language' => $data['cpm_language'],
            'cpm_trans_date' => $data['cpm_trans_date'],
            'cpm_trans_id' => $data['cpm_trans_id'],
            'cpm_designation' => $data['cpm_designation'],
            'cpm_amount' => $data['cpm_amount'],
            'signature' => $signature,
        ];

        if (!empty($data['cpm_custom'])) {
            $params['cpm_custom'] = $data['cpm_custom'];
        }

        if (!empty($this->notifyUrl)) {
            $params['notify_url'] = $this->notifyUrl;
        }

        if (!empty($this->returnUrl)) {
            $params['return_url'] = $this->returnUrl;
        }

        if (!empty($this->cancelUrl)) {
            $params['cancel_url'] = $this->cancelUrl;
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Obtenir l'URL du cash desk CinetPay
     */
    protected function getCashDeskUrl(): string
    {
        // CinetPay utilise toujours HTTPS
        $host = $this->mode === 'production'
            ? 'secure.cinetpay.com'
            : 'secure.sandbox.cinetpay.com';

        return 'https://' . $host;
    }

    /**
     * Obtenir l'URL pour obtenir la signature
     */
    protected function getSignatureUrl(): string
    {
        // CinetPay utilise toujours HTTPS
        $host = $this->mode === 'production'
            ? 'api-checkout.cinetpay.com'
            : 'api.sandbox.cinetpay.com';

        $version = strtolower($this->version);
        // Nouvelle structure d'URL pour le nouvel endpoint
        return 'https://' . $host . '/' . $version . '/payment/signature';
    }

    /**
     * Vérifier le statut d'un paiement via l'API V2
     *
     * @param string $transactionId
     * @return array
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        $payload = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
        ];

        // Construire l'URL de manière fiable
        $baseUrl = rtrim(config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com'), '/');
        $url = $baseUrl . '/v2/payment/check';

        try {
            $response = Http::asJson()->timeout(30)->post($url, $payload);

            if ($response->failed()) {
                Log::error('Erreur API CinetPay (checkPaymentStatus)', [
                    'status' => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                    'transaction_id' => $transactionId,
                ]);
                return ['success' => false, 'error' => 'Erreur lors de la vérification du statut'];
            }

            $data = $response->json();

            if (isset($data['code']) && $data['code'] === '0') {
                $paymentData = $data['data'];
                return [
                    'success' => true,
                    'data' => $paymentData,
                    'status' => $paymentData['status'] ?? 'UNKNOWN',
                    'message' => $data['message'],
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Statut de paiement non valide',
                'data' => $data,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Timeout de connexion à CinetPay (checkPaymentStatus)', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);
            return ['success' => false, 'error' => 'Service de paiement indisponible'];
        }
    }

    /**
     * Obtenir l'URL pour vérifier le statut du paiement
     */
    protected function getCheckPayStatusUrl(): string
    {
        // CinetPay utilise toujours HTTPS
        $host = $this->mode === 'production'
            ? 'api-checkout.cinetpay.com'
            : 'api.sandbox.cinetpay.com';

        $version = strtolower($this->version);
        // Mettre à jour également ce chemin, en supposant une structure similaire
        return 'https://' . $host . '/' . $version . '/payment/check';
    }

    /**
     * Générer un ID de transaction unique
     *
     * @return string
     */
    protected function generateTransactionId(): string
    {
        $timestamp = time();
        $parts = explode(' ', microtime());
        $id = ($timestamp + $parts[0] - strtotime('today 00:00')) * 10;
        $id = sprintf('%06d', $id) . mt_rand(100, 9999);

        return 'NETLAB_' . $id;
    }

    /**
     * Vérifier l'état de santé de l'API CinetPay
     *
     * @return array
     */
    public function checkHealth(): array
    {
        $health = [
            'status' => 'unknown',
            'timestamp' => now()->toIso8601String(),
            'configuration' => [],
            'connectivity' => [],
            'api_status' => [],
            'overall_health' => 'unknown',
        ];

        // 1. Vérifier la configuration (depuis la base de données avec fallback)
        $apiKey = Setting::get('cinetpay.api_key', config('services.cinetpay.api_key') ?? env('CINETPAY_API_KEY', ''));
        $siteId = Setting::get('cinetpay.site_id', config('services.cinetpay.site_id') ?? env('CINETPAY_SITE_ID', ''));
        $mode = Setting::get('cinetpay.mode', config('services.cinetpay.mode') ?? env('CINETPAY_MODE', 'sandbox'));
        $notifyUrl = Setting::get('cinetpay.notify_url', config('services.cinetpay.notify_url') ?? env('CINETPAY_NOTIFY_URL', url('/api/payments/cinetpay/webhook')));
        $returnUrl = Setting::get('cinetpay.return_url', config('services.cinetpay.return_url') ?? env('CINETPAY_RETURN_URL', url('/api/payments/return')));
        $cancelUrl = Setting::get('cinetpay.cancel_url', config('services.cinetpay.cancel_url') ?? env('CINETPAY_CANCEL_URL', url('/api/payments/cancel')));
        
        $health['configuration'] = [
            'api_key' => !empty($apiKey) ? '✓ Défini (' . substr($apiKey, 0, 8) . '...)' : '✗ Manquant',
            'site_id' => !empty($siteId) ? '✓ Défini (' . $siteId . ')' : '✗ Manquant',
            'mode' => $mode ?? 'non défini',
            'notify_url' => $notifyUrl ?? url('/api/payments/cinetpay/webhook'),
            'return_url' => $returnUrl ?? url('/api/payments/return'),
            'cancel_url' => $cancelUrl ?? url('/api/payments/cancel'),
        ];

        $configValid = !empty($apiKey) && !empty($siteId) && !empty($mode);

        // 2. Vérifier la connectivité réseau avec le nouveau endpoint
        $paymentUrl = rtrim(config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com'), '/') . '/v2/payment';
        $connectivityStart = microtime(true);
        
        try {
            // On envoie une requête simple pour tester la connectivité.
            // Une erreur 401/422 est attendue si la connexion réussit mais que les données sont mauvaises,
            // ce qui prouve que le service est joignable.
            $response = Http::timeout(10)->asJson()->post($paymentUrl, [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
            ]);
            
            $connectivityDuration = round((microtime(true) - $connectivityStart) * 1000, 2);
            
            // Une erreur client (4xx) signifie que nous avons bien atteint le service.
            $isReachable = $response->successful() || $response->clientError();

            $health['connectivity'] = [
                'status' => $isReachable ? 'reachable' : 'unreachable',
                'response_time_ms' => $connectivityDuration,
                'http_status' => $response->status(),
                'url' => $paymentUrl,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $connectivityDuration = round((microtime(true) - $connectivityStart) * 1000, 2);
            $health['connectivity'] = [
                'status' => 'timeout',
                'response_time_ms' => $connectivityDuration,
                'error' => 'Timeout de connexion (>10s)',
                'url' => $paymentUrl,
            ];
        } catch (\Exception $e) {
            $connectivityDuration = round((microtime(true) - $connectivityStart) * 1000, 2);
            $health['connectivity'] = [
                'status' => 'error',
                'response_time_ms' => $connectivityDuration,
                'error' => $e->getMessage(),
                'url' => $paymentUrl,
            ];
        }

        // 3. Tester l'initialisation d'un paiement (sans créer de transaction réelle)
        $apiTestStart = microtime(true);
        try {
            // Test avec un montant minimal (100 XOF = 10000 centimes)
            $testPaymentData = [
                'amount' => 10000, // 100 XOF en centimes
                'currency' => 'XOF',
                'description' => 'Test de santé API - ' . now()->toDateTimeString(),
                'transaction_id' => 'HEALTH_CHECK_' . time(),
            ];

            $testResult = $this->initiatePayment($testPaymentData);
            $apiTestDuration = round((microtime(true) - $apiTestStart) * 1000, 2);

            $health['api_status'] = [
                'status' => $testResult['success'] ? 'operational' : 'error',
                'response_time_ms' => $apiTestDuration,
                'can_initiate_payment' => $testResult['success'],
                'error' => $testResult['error'] ?? null,
                'code' => $testResult['code'] ?? null,
            ];
        } catch (\Exception $e) {
            $apiTestDuration = round((microtime(true) - $apiTestStart) * 1000, 2);
            $health['api_status'] = [
                'status' => 'error',
                'response_time_ms' => $apiTestDuration,
                'can_initiate_payment' => false,
                'error' => $e->getMessage(),
            ];
        }

        // 4. Déterminer l'état de santé global
        $issues = [];
        
        if (!$configValid) {
            $issues[] = 'Configuration incomplète';
        }
        
        if ($health['connectivity']['status'] === 'timeout' || $health['connectivity']['status'] === 'error') {
            $issues[] = 'API non accessible';
        }
        
        if ($health['api_status']['status'] === 'error' || !$health['api_status']['can_initiate_payment']) {
            $issues[] = 'Impossible d\'initialiser un paiement';
        }

        if (empty($issues)) {
            $health['overall_health'] = 'healthy';
            $health['status'] = 'operational';
        } elseif (count($issues) === 1 && $issues[0] === 'Configuration incomplète') {
            $health['overall_health'] = 'degraded';
            $health['status'] = 'misconfigured';
        } else {
            $health['overall_health'] = 'unhealthy';
            $health['status'] = 'down';
        }

        $health['issues'] = $issues;
        $health['summary'] = [
            'config_valid' => $configValid,
            'api_reachable' => in_array($health['connectivity']['status'], ['reachable']),
            'payment_working' => $health['api_status']['can_initiate_payment'] ?? false,
        ];

        return $health;
    }
}
