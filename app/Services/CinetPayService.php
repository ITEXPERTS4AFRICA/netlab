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
    protected string $version; // Version de l'API CinetPay (v1 par défaut)

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

            // Version de l'API CinetPay (v1 par défaut pour les endpoints de signature)
            $this->version = env('CINETPAY_API_VERSION', 'v1');

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
            $this->version = env('CINETPAY_API_VERSION', 'v1');
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
            // Timeout réduit à 20 secondes pour éviter les attentes trop longues
            // En production, on peut augmenter si nécessaire
            $timeout = app()->environment('production') ? 30 : 20;
            $httpClient = Http::asJson()
                ->timeout($timeout)
                ->connectTimeout(10); // Timeout de connexion à 10 secondes
            
            // Désactiver la vérification SSL en développement local pour éviter les erreurs de certificat
            // En production, on devrait avoir un certificat valide
            if (!app()->environment('production')) {
                $httpClient = $httpClient->withoutVerifying();
            }
            
            $response = $httpClient->post($url, $payload);
            
            // Log de la réponse brute pour déboguer
            $responseBody = $response->body();
            $responseHeaders = $response->headers();
            $contentType = $responseHeaders['content-type'][0] ?? $responseHeaders['Content-Type'][0] ?? '';
            
            Log::info('CinetPay raw response', [
                'status' => $response->status(),
                'content_type' => $contentType,
                'body_preview' => substr($responseBody, 0, 500),
                'url' => $url,
            ]);
            
            // Détecter si la réponse est HTML (404, erreur serveur, etc.)
            $isHtmlResponse = strpos(strtolower($contentType), 'text/html') !== false 
                || strpos(strtolower($responseBody), '<!doctype html') !== false
                || strpos(strtolower($responseBody), '<html') !== false;
            
            if ($isHtmlResponse) {
                Log::error('CinetPay a retourné une page HTML au lieu de JSON (URL probablement incorrecte)', [
                    'status' => $response->status(),
                    'url' => $url,
                    'content_type' => $contentType,
                    'body_preview' => substr($responseBody, 0, 500),
                ]);
                
                // Extraire le titre de la page HTML si possible
                $htmlTitle = 'Page non trouvée';
                if (preg_match('/<title>(.*?)<\/title>/i', $responseBody, $matches)) {
                    $htmlTitle = $matches[1];
                }
                
                return [
                    'success' => false,
                    'error' => 'L\'URL de l\'API CinetPay est incorrecte. Vérifiez la configuration CINETPAY_API_URL dans votre fichier .env (doit être: https://api-checkout.cinetpay.com)',
                    'code' => 'INVALID_URL',
                    'description' => "L'API a retourné une page HTML ({$htmlTitle}) au lieu d'une réponse JSON. L'URL utilisée est probablement incorrecte.",
                    'is_timeout' => false,
                    'url_used' => $url,
                    'expected_url' => 'https://api-checkout.cinetpay.com/v2/payment',
                ];
            }
            
            if ($response->failed()) {
                $errorBody = $response->json() ?? ['message' => $responseBody];
                Log::error('Erreur API CinetPay (initiatePayment)', [
                    'status' => $response->status(),
                    'response' => $errorBody,
                    'url' => $url,
                ]);
                return [
                    'success' => false,
                    'error' => 'L\'API CinetPay a retourné une erreur: ' . ($errorBody['message'] ?? 'Erreur inconnue'),
                    'code' => 'API_ERROR',
                    'description' => $errorBody['description'] ?? null,
                    'is_timeout' => false,
                ];
            }
            
            $data = $response->json();
            
            // Vérifier que la réponse est bien du JSON
            if ($data === null && !empty($responseBody)) {
                Log::error('CinetPay a retourné une réponse non-JSON', [
                    'status' => $response->status(),
                    'content_type' => $contentType,
                    'body_preview' => substr($responseBody, 0, 500),
                    'url' => $url,
                ]);
                return [
                    'success' => false,
                    'error' => 'L\'API CinetPay a retourné une réponse invalide (non-JSON). Vérifiez la configuration CINETPAY_API_URL dans votre fichier .env.',
                    'code' => 'INVALID_RESPONSE',
                    'description' => 'La réponse n\'est pas au format JSON attendu.',
                    'is_timeout' => false,
                    'url_used' => $url,
                ];
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
            $errorMessage = $e->getMessage();
            $isSslError = strpos($errorMessage, 'SSL certificate') !== false || strpos($errorMessage, 'cURL error 60') !== false;
            
            Log::error('Erreur de connexion à CinetPay (initiatePayment)', [
                'error' => $errorMessage,
                'url' => $url,
                'is_ssl_error' => $isSslError,
            ]);
            
            if ($isSslError) {
                return [
                    'success' => false,
                    'error' => 'Erreur de certificat SSL. Le problème devrait être résolu automatiquement. Veuillez réessayer.',
                    'code' => 'SSL_ERROR',
                    'description' => 'Problème de certificat SSL lors de la connexion à CinetPay. En développement, la vérification SSL est désactivée.',
                    'is_timeout' => false,
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Le service de paiement est indisponible pour le moment. Veuillez réessayer plus tard.',
                'code' => 'CONNECTION_TIMEOUT',
                'is_timeout' => true,
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $isSslError = strpos($errorMessage, 'SSL certificate') !== false || strpos($errorMessage, 'cURL error 60') !== false;
            
            Log::error('Erreur inattendue lors de l\'initiation du paiement CinetPay', [
                'error' => $errorMessage,
                'url' => $url,
                'is_ssl_error' => $isSslError,
                'trace' => $e->getTraceAsString(),
            ]);
            
            if ($isSslError) {
                return [
                    'success' => false,
                    'error' => 'Erreur de certificat SSL. Le problème devrait être résolu automatiquement. Veuillez réessayer.',
                    'code' => 'SSL_ERROR',
                    'description' => 'Problème de certificat SSL lors de la connexion à CinetPay.',
                    'is_timeout' => false,
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Une erreur inattendue s\'est produite lors de l\'initialisation du paiement.',
                'code' => 'UNKNOWN_ERROR',
                'is_timeout' => false,
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
     * Vérifier le token HMAC du webhook CinetPay
     * 
     * Selon la documentation CinetPay :
     * - Le token HMAC est dans l'en-tête 'x-token'
     * - Il doit être calculé avec les données du corps de la requête
     * - La clé secrète est l'API Key
     * 
     * @param array $webhookData Les données du webhook (corps de la requête)
     * @param string $receivedToken Le token reçu dans l'en-tête x-token
     * @return bool True si le token est valide, False sinon
     */
    public function verifyWebhookHmacToken(array $webhookData, string $receivedToken): bool
    {
        try {
            // Construire la chaîne à hacher selon la documentation CinetPay
            // L'ordre des champs est important pour le calcul du HMAC
            $fields = [
                'cpm_site_id',
                'cpm_trans_id',
                'cpm_trans_date',
                'cpm_amount',
                'cpm_currency',
                'signature',
                'payment_method',
                'cel_phone_num',
                'cpm_phone_prefixe',
                'cpm_language',
                'cpm_version',
                'cpm_payment_config',
                'cpm_page_action',
                'cpm_custom',
                'cpm_designation',
                'cpm_error_message',
            ];

            // Construire la chaîne de données pour le HMAC
            $dataString = '';
            foreach ($fields as $field) {
                $value = $webhookData[$field] ?? '';
                // Convertir en string et nettoyer
                $value = (string)$value;
                $dataString .= $value;
            }

            // Calculer le HMAC SHA256 avec l'API Key comme clé secrète
            $calculatedToken = hash_hmac('sha256', $dataString, $this->apiKey);

            // Comparer les tokens (comparaison sécurisée pour éviter les attaques par timing)
            $isValid = hash_equals($calculatedToken, $receivedToken);

            Log::info('CinetPay HMAC token verification', [
                'is_valid' => $isValid,
                'received_token_preview' => substr($receivedToken, 0, 20) . '...',
                'calculated_token_preview' => substr($calculatedToken, 0, 20) . '...',
                'data_string_length' => strlen($dataString),
                'fields_count' => count($fields),
            ]);

            if (!$isValid) {
                Log::warning('CinetPay HMAC token invalide', [
                    'received_token_length' => strlen($receivedToken),
                    'calculated_token_length' => strlen($calculatedToken),
                    'data_string_preview' => substr($dataString, 0, 100),
                ]);
            }

            return $isValid;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du token HMAC CinetPay', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Vérifier le token HMAC du webhook (méthode alternative avec tous les champs)
     * 
     * Cette méthode utilise tous les champs présents dans les données du webhook
     * pour calculer le HMAC, ce qui est plus flexible si CinetPay ajoute des champs.
     * 
     * @param array $webhookData Les données du webhook
     * @param string $receivedToken Le token reçu dans l'en-tête x-token
     * @return bool True si le token est valide
     */
    public function verifyWebhookHmacTokenFlexible(array $webhookData, string $receivedToken): bool
    {
        try {
            // Trier les clés pour garantir un ordre cohérent
            ksort($webhookData);

            // Construire la chaîne de données
            $dataString = '';
            foreach ($webhookData as $key => $value) {
                // Ignorer certains champs qui ne font pas partie du calcul HMAC
                if (in_array($key, ['x-token', 'webhook_data', 'cinetpay_response'])) {
                    continue;
                }
                $dataString .= (string)$value;
            }

            // Calculer le HMAC
            $calculatedToken = hash_hmac('sha256', $dataString, $this->apiKey);

            // Comparer
            $isValid = hash_equals($calculatedToken, $receivedToken);

            Log::info('CinetPay HMAC token verification (flexible)', [
                'is_valid' => $isValid,
                'received_token_preview' => substr($receivedToken, 0, 20) . '...',
                'calculated_token_preview' => substr($calculatedToken, 0, 20) . '...',
                'data_string_length' => strlen($dataString),
            ]);

            return $isValid;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification flexible du token HMAC', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
