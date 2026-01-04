<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\CiscoApiService;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, CiscoApiService $cisco ): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $remember = $request->boolean('remember');

        if ($this->attemptLocalLogin($request, $remember)) {
            $request->session()->regenerate();
            RateLimiter::clear($request->throttleKey());

            if (! app()->environment('testing')) {
                $this->attemptCmlAuthentication($request, $cisco, $remember, strict: false);
            }

            return redirect()->intended(route('dashboard', absolute: false));
        }

        $this->attemptCmlAuthentication($request, $cisco, $remember, strict: true);

        $request->session()->regenerate();
        RateLimiter::clear($request->throttleKey());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {


        // Try to revoke CML token via service if available
        try {
            $ciscoService = app(\App\Services\CiscoApiService::class);
            $cmlToken = session('cml_token');

            if ($cmlToken) {
                // Utiliser le service auth pour logout et revokeToken
                $ciscoService->auth->logout($cmlToken);
                $ciscoService->auth->revokeToken();
            }
        } catch (\Exception $e) {
            // ignore revoke errors - best effort
            Log::debug('Erreur lors de la révocation du token CML (non bloquant)', [
                'error' => $e->getMessage(),
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Attempt to authenticate against the local database.
     */
    protected function attemptLocalLogin(LoginRequest $request, bool $remember): bool
    {
        $password = $request->input('password');

        $credentials = [];

        if ($request->filled('email')) {
            $credentials[] = ['email' => $request->input('email'), 'password' => $password];
        }

        if ($request->filled('username')) {
            $credentials[] = ['name' => $request->input('username'), 'password' => $password];
        }

        foreach ($credentials as $data) {
            if (Auth::attempt($data, $remember)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attempt to authenticate against Cisco CML and persist the session token.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function attemptCmlAuthentication(LoginRequest $request, CiscoApiService $cisco, bool $remember, bool $strict): bool
    {
        $username = $request->input('username') ?? $request->input('email');
        $password = $request->input('password');

        if (! $username || ! $password) {
            if ($strict) {
                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                ]);
            }

            return false;
        }

        $attempts = 0;
        $maxAttempts = 3;
        $result = null;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $result = $cisco->auth_extended($username, $password);

            if (is_array($result) && ! isset($result['error'])) {
                break;
            }

            usleep(250000);
        }

        if (! is_array($result) || isset($result['error'])) {
            if ($strict) {
                $message = __('auth.failed');

                if (is_array($result) && isset($result['error'])) {
                    logger()->warning('CML auth failed', ['error' => $result['error']]);
                }

                RateLimiter::hit($request->throttleKey());

                throw ValidationException::withMessages([
                    'email' => $message,
                ]);
            }

            return false;
        }

        if (isset($result['token'])) {
            $cisco->setToken($result['token']);
        }

        if (! Auth::check()) {
            $email = $request->input('email') ?: $username.'@local.netlab';

            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $username,
                    'email' => $email,
                    'password' => bcrypt(Str::random(40)),
                ]);
            }

            Auth::login($user, $remember);
        }

        return true;
    }
}
