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
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        $token = session('cml_token');

        if (!$token) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Veuillez vous authentifier auprès de CML.'),
                ], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()
                ->route('login')
                ->with('error', 'Veuillez vous connecter.');
        }
        return $next($request);
    }
}
