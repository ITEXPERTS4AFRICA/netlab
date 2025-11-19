<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            \Log::warning('Tentative d\'accès admin sans authentification', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Authentification requise.',
                    'error' => 'Unauthenticated',
                ], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->isAdmin()) {
            \Log::warning('Tentative d\'accès admin par un utilisateur non-admin', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Accès refusé. Administrateur requis.',
                    'error' => 'Forbidden',
                    'user_role' => $user->role,
                ], 403);
            }
            abort(403, 'Accès refusé. Vous devez être administrateur pour accéder à cette page.');
        }

        return $next($request);
    }
}
