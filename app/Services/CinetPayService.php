<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $config = config('services.cinetpay');
        $this->apiKey = $config['api_key'];
        $this->siteId = $config['site_id'];
        $this->notifyUrl = $config['notify_url'] ?? url('/api/payments/cinetpay/webhook');
        $this->returnUrl = $config['return_url'] ?? url('/api/payments/return');
        $this->cancelUrl = $config['cancel_url'] ?? url('/api/payments/cancel');
        $this->mode = $config['mode'] ?? 'sandbox';
        $this->version = 'V2';

        // Initialiser le SDK officiel CinetPay
        // Désactiver l'affichage du CSS en passant ['style' => false] comme 5ème paramètre
        $platform = strtoupper($this->mode) === 'PRODUCTION' ? 'PROD' : 'TEST';

        // Capturer la sortie pour empêcher le SDK d'afficher du CSS
        ob_start();
        $this->cinetPay = new \CinetPay($this->siteId, $this->apiKey, $platform, $this->version, ['style' => false]);
        $output = ob_get_clean(); // Capturer et nettoyer la sortie

        // Si du CSS a été affiché malgré tout, le logger mais ne pas l'inclure dans la réponse
        if (!empty($output) && strpos($output, '<style>') !== false) {
            \Log::warning('CinetPay SDK output captured', ['output_length' => strlen($output)]);
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

            // Obtenir la signature via le SDK officiel
            $signature = $this->cinetPay->getSignature();

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

            Log::error('CinetPay payment initiation error via SDK', [
                'error' => $errorMessage,
                'code' => $errorCode,
                'description' => $errorDescription,
                'transaction_id' => $transactionId,
                'amount_in_cents' => $amountInCents,
                'amount_in_xof' => $amount,
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de l\'initialisation du paiement: ' . $errorMessage,
                'code' => $errorCode,
                'description' => $errorDescription,
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
            // Augmenter le timeout à 30 secondes
            $response = Http::timeout(30)->asForm()->post($url, $data);

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
}
