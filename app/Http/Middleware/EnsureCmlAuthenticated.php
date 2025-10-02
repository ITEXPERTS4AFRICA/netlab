<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmlAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,): Response
    {

        $token = session('cml_token');
        
        if (!$token) {
            return redirect()->route('login',['error' => 'Veuillez vous connecter.'])->withErrors(['message' => 'Veuillez vous connecter.']);
        }
        return $next($request);
    }
}
