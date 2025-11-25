<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Exclure toutes les routes API du middleware CSRF
        // Les routes API utilisent généralement des tokens d'authentification (Bearer tokens)
        // plutôt que des tokens CSRF
        'api/*',
        
        // Exclure aussi les webhooks qui viennent de services externes
        'api/payments/cinetpay/webhook',
    ];
}
