<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class CinetPayService
{
    protected string $apiKey;
    protected string $siteId;
    protected string $apiUrl;
    protected string $notifyUrl;
    protected string $returnUrl;
    protected string $cancelUrl;
    protected string $mode;

    public function __construct()
    {
        $config = config('services.cinetpay');
        $this->apiKey = $config['api_key'];
        $this->siteId = $config['site_id'];
        $this->apiUrl = $config['api_url'];
        // Utiliser l'URL absolue pour les webhooks
        $this->notifyUrl = $config['notify_url'] ?? url('/api/payments/cinetpay/webhook');
        $this->returnUrl = $config['return_url'] ?? url('/api/payments/return');
        $this->cancelUrl = $config['cancel_url'] ?? url('/api/payments/cancel');
        $this->mode = $config['mode'] ?? 'sandbox';
    }

    /**
     * Initialiser un paiement
     *
     * @param array $paymentData
     * @return array
     */
    public function initiatePayment(array $paymentData): array
    {
        $checkoutUrl = $this->mode === 'production' 
            ? 'https://api-checkout.cinetpay.com/v2/payment'
            : 'https://api-checkout.cinetpay.com/v2/payment';

        $data = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $paymentData['transaction_id'] ?? $this->generateTransactionId(),
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'XOF',
            'alternative_currency' => $paymentData['alternative_currency'] ?? '',
            'description' => $paymentData['description'] ?? 'Paiement de réservation',
            'customer_id' => $paymentData['customer_id'],
            'customer_name' => $paymentData['customer_name'],
            'customer_surname' => $paymentData['customer_surname'] ?? '',
            'customer_email' => $paymentData['customer_email'],
            'customer_phone_number' => $paymentData['customer_phone_number'],
            'customer_address' => $paymentData['customer_address'] ?? '',
            'customer_city' => $paymentData['customer_city'] ?? '',
            'customer_country' => $paymentData['customer_country'] ?? 'CM',
            'customer_state' => $paymentData['customer_state'] ?? 'CM',
            'customer_zip_code' => $paymentData['customer_zip_code'] ?? '',
            'notify_url' => $this->notifyUrl,
            'return_url' => $this->returnUrl,
            'channels' => $paymentData['channels'] ?? 'ALL',
            'metadata' => $paymentData['metadata'] ?? '',
            'lang' => $paymentData['lang'] ?? 'FR',
            'invoice_data' => $paymentData['invoice_data'] ?? [],
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($checkoutUrl, $data);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] === '201') {
                return [
                    'success' => true,
                    'data' => $responseData['data'],
                    'payment_url' => $responseData['data']['payment_url'] ?? null,
                    'transaction_id' => $data['transaction_id'],
                ];
            }

            return [
                'success' => false,
                'error' => $responseData['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                'code' => $responseData['code'] ?? 'UNKNOWN',
            ];
        } catch (\Exception $e) {
            Log::error('CinetPay payment initiation error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => 'Erreur de connexion à CinetPay: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier le statut d'un paiement
     *
     * @param string $transactionId
     * @return array
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        $url = $this->apiUrl . '/v1/transfer/check/money';

        $data = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $data);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] === '00') {
                return [
                    'success' => true,
                    'data' => $responseData['data'],
                    'status' => $responseData['data']['status'] ?? 'UNKNOWN',
                ];
            }

            return [
                'success' => false,
                'error' => $responseData['message'] ?? 'Erreur lors de la vérification du paiement',
                'code' => $responseData['code'] ?? 'UNKNOWN',
            ];
        } catch (\Exception $e) {
            Log::error('CinetPay payment status check error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'error' => 'Erreur de connexion à CinetPay: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier la signature d'un webhook
     *
     * @param array $webhookData
     * @return bool
     */
    public function verifyWebhookSignature(array $webhookData): bool
    {
        // CinetPay envoie généralement les données avec une signature
        // À adapter selon la documentation CinetPay
        if (!isset($webhookData['cpm_trans_id']) || !isset($webhookData['cpm_amount'])) {
            return false;
        }

        // Vérifier le statut du paiement via l'API
        $status = $this->checkPaymentStatus($webhookData['cpm_trans_id']);

        return $status['success'] && ($status['status'] ?? '') === 'ACCEPTED';
    }

    /**
     * Générer un ID de transaction unique
     *
     * @return string
     */
    protected function generateTransactionId(): string
    {
        return 'NETLAB_' . time() . '_' . mt_rand(100000, 999999);
    }
}

