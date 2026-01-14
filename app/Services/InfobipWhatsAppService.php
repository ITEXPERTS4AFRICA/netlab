<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfobipWhatsAppService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $sender;

    public function __construct()
    {
        // On récupère les valeurs de ton .env
        $this->baseUrl = config('services.infobip.base_url');
        $this->apiKey = config('services.infobip.api_key');
        $this->sender = config('services.infobip.whatsapp_sender');
    }

    public function sendOtp(string $phone, string $code): bool
    {
        // 1. Nettoyage simple du numéro de téléphone (supprime les espaces)
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        $message = "Votre code de vérification NETLAB est : *{$code}*.";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/whatsapp/1/message/text', [
                'from' => $this->sender,
                'to' => $phone,
                'message' => [
                    'text' => $message
                ]
            ]);

            // IMPORTANT : Si ça échoue, on jette l'erreur pour voir ce qui se passe dans les logs
            if (!$response->successful()) {
                $errorMessage = "Infobip Error: " . $response->body();
                Log::error($errorMessage);

                // On lance une exception pour que le contrôleur puisse la capter
                throw new \Exception($errorMessage);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Infobip Exception: ' . $e->getMessage());
            // On relance pour que le Controller la prenne en compte
            throw $e;
        }
    }
}
