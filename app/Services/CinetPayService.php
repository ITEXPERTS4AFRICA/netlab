<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

// Inclure le SDK officiel CinetPay
if (!class_exists('CinetPay')) {
    require_once base_path('cinetpay-php-sdk-master/src/cinetpay.php');
}

class CinetPayService
{
    protected string $apiKey;
    protected string $siteId;
    protected string $notifyUrl;
    protected string $returnUrl;
    protected string $cancelUrl;
    protected string $mode;
    protected string $version;
    protected \CinetPay $cinetPay;

    public function __construct()
    {
        try {
            // Lire depuis la base de données avec fallback sur .env
            $config = config('services.cinetpay', []);
            
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
            $mode = strtolower(trim((string)$mode));
            if (strpos($mode, 'production') !== false || strpos($mode, 'prod') !== false) {
                $this->mode = 'production';
            } elseif (strpos($mode, 'sandbox') !== false || strpos($mode, 'test') !== false) {
                $this->mode = 'sandbox';
            } else {
                $this->mode = 'sandbox'; // Par défaut
            }
            
            $this->version = 'V2';

            // Initialiser le SDK officiel CinetPay seulement si les credentials sont fournis
            if (!empty($this->apiKey) && !empty($this->siteId) && $this->apiKey !== 'temp_key' && $this->siteId !== 'temp_site') {
                // Initialiser le SDK officiel CinetPay
                // Désactiver l'affichage du CSS en passant ['style' => false] comme 5ème paramètre
                $platform = strtoupper($this->mode) === 'PRODUCTION' ? 'PROD' : 'TEST';

                // Capturer la sortie pour empêcher le SDK d'afficher du CSS
                ob_start();
                try {
                    $this->cinetPay = new \CinetPay($this->siteId, $this->apiKey, $platform, $this->version, ['style' => false]);
                } catch (\Exception $e) {
                    // Si le SDK ne peut pas être initialisé, logger mais continuer
                    Log::warning('CinetPay SDK initialization error', [
                        'error' => $e->getMessage(),
                        'site_id' => substr($this->siteId, 0, 4) . '...',
                    ]);
                    // Créer un objet null pour éviter les erreurs fatales
                    $this->cinetPay = null;
                }
                $output = ob_get_clean(); // Capturer et nettoyer la sortie

                // Si du CSS a été affiché malgré tout, le logger mais ne pas l'inclure dans la réponse
                if (!empty($output) && strpos($output, '<style>') !== false) {
                    Log::warning('CinetPay SDK output captured', ['output_length' => strlen($output)]);
                }
            } else {
                // Credentials manquants ou invalides, ne pas initialiser le SDK
                $this->cinetPay = null;
                Log::debug('CinetPay SDK not initialized - credentials missing or invalid');
            }
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
            $this->version = 'V2';
            $this->cinetPay = null;
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
        // Vérifier que le SDK est initialisé
        if (!$this->cinetPay) {
            Log::error('CinetPay SDK not initialized - cannot initiate payment', [
                'api_key_set' => !empty($this->apiKey) && $this->apiKey !== 'temp_key',
                'site_id_set' => !empty($this->siteId) && $this->siteId !== 'temp_site',
                'mode' => $this->mode,
            ]);
            
            return [
                'success' => false,
                'error' => 'CinetPay n\'est pas configuré. Veuillez configurer les credentials CinetPay dans les paramètres d\'administration.',
                'code' => 'SDK_NOT_INITIALIZED',
                'description' => 'Les credentials CinetPay (API Key et Site ID) ne sont pas configurés.',
            ];
        }
        
        // CinetPay attend le montant directement en XOF (pas en centimes)
        // Le montant est déjà en centimes dans $paymentData['amount']
        // Convertir les centimes en XOF
        $amountInCents = $paymentData['amount'];

        // Log pour vérifier le montant reçu
        Log::info('CinetPayService: Montant reçu', [
            'amount_in_cents' => $amountInCents,
            'amount_type' => gettype($amountInCents),
        ]);

        // Convertir les centimes en XOF (diviser par 100)
        // Le montant est toujours en centimes depuis ReservationController
        $amount = $amountInCents / 100;
        $amount = (int) round($amount); // Arrondir et s'assurer que c'est un entier

        // CinetPay requiert un montant minimum de 100 XOF
        // Vérifier le montant minimum avant de continuer
        $minAmountXOF = 100; // Montant minimum requis par CinetPay
        $minAmountCents = $minAmountXOF * 100; // 10000 centimes

        if ($amount < $minAmountXOF) {
            Log::warning('CinetPayService: Montant trop faible', [
                'amount_in_cents' => $amountInCents,
                'amount_in_xof' => $amount,
                'min_amount_xof' => $minAmountXOF,
                'min_amount_cents' => $minAmountCents,
            ]);

            return [
                'success' => false,
                'error' => "Le montant minimum requis est de {$minAmountXOF} XOF (soit {$minAmountCents} centimes). Le montant actuel est de {$amount} XOF.",
                'code' => '641',
                'description' => 'ERROR_AMOUNT_TOO_LOW',
            ];
        }

        // Log pour vérifier la conversion
        Log::info('CinetPayService: Montant converti', [
            'amount_in_cents' => $amountInCents,
            'amount_in_xof' => $amount,
            'currency' => $paymentData['currency'] ?? 'XOF',
        ]);

        // Générer un transaction_id si non fourni
        $transactionId = $paymentData['transaction_id'] ?? \CinetPay::generateTransId();

        // Date de transaction au format Y-m-d H:i:s (le SDK la convertira)
        $transDate = date('Y-m-d H:i:s');

        try {
            // Utiliser le SDK officiel pour configurer le paiement
            $this->cinetPay->setTransId($transactionId)
                ->setDesignation($paymentData['description'] ?? 'Paiement de réservation')
                ->setTransDate($transDate)
                ->setAmount($amount)
                ->setCurrency($paymentData['currency'] ?? 'XOF')
                ->setDebug(false);

            // Ajouter cpm_custom si fourni
            if (!empty($paymentData['customer_id'])) {
                $this->cinetPay->setCustom((string) $paymentData['customer_id']);
            }

            // Ajouter les URLs
            if (!empty($this->notifyUrl)) {
                $this->cinetPay->setNotifyUrl($this->notifyUrl);
            }
            if (!empty($this->returnUrl)) {
                $this->cinetPay->setReturnUrl($this->returnUrl);
            }
            if (!empty($this->cancelUrl)) {
                $this->cinetPay->setCancelUrl($this->cancelUrl);
            }

            // Obtenir la signature via le SDK officiel avec gestion de timeout
            try {
                // Vérifier que le SDK est bien initialisé avant d'appeler getSignature()
                if (!$this->cinetPay) {
                    Log::error('CinetPay SDK is null when trying to get signature', [
                        'api_key_set' => !empty($this->apiKey) && $this->apiKey !== 'temp_key',
                        'site_id_set' => !empty($this->siteId) && $this->siteId !== 'temp_site',
                        'mode' => $this->mode,
                    ]);
                    
                    return [
                        'success' => false,
                        'error' => 'CinetPay n\'est pas configuré. Veuillez configurer les credentials CinetPay dans les paramètres d\'administration.',
                        'code' => 'SDK_NOT_INITIALIZED',
                        'description' => 'Les credentials CinetPay (API Key et Site ID) ne sont pas configurés.',
                    ];
                }
                
                Log::info('CinetPay: Appel de getSignature()', [
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'currency' => $paymentData['currency'] ?? 'XOF',
                    'mode' => $this->mode,
                ]);
                
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
                    $errorMessage = 'L\'API sandbox de CinetPay est temporairement indisponible ou ne répond pas. Le timeout de connexion (45 secondes) a été dépassé. Veuillez réessayer plus tard ou contacter le support si le problème persiste.';
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
