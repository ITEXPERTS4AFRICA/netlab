<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InfobipWhatsAppService
{
    public function sendOtp(string $phone, string $code)
    {
        $response = Http::withHeaders([
            'Authorization' => 'App ' . config('infobip.api_key'),
            'Content-type' => 'application/json',
            'Accept' => 'application/json'
        ])->post(
            config('infobip.base_url') . '/whatsapp/1/message/text',
            [
                'from' => config('infobip.whatsapp_sender'),
                'to' => $phone,
                'content' => [
                    'text' => "votre code de verification est : {$code}. Il expire dans 5 minutes."
                ]
            ]
        );


        if(! $response->successful()){
            Log::error('Infobip WhatsApp OTP failed',[
                'response' => $response->json(),
            ]);

            throw new RuntimeException("Impossible d'envoyer le code OTP.");
        
        }
    }
}
