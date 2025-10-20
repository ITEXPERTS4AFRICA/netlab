<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\CiscoApiService;
use App\Models\User;

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
        $request->session()->regenerate();

        $request->validate([
            'username' => 'required|string',
            'password' => 'required'
        ]);

        $username = $request->username;
        $password = $request->password;

        // Try a limited number of attempts to authenticate against CML
        $attempts = 0;
        $maxAttempts = 3;
        $result = null;
        while ($attempts < $maxAttempts) {
            $attempts++;
            $result = $cisco->auth_extended($username, $password);
            if (is_array($result) && !isset($result['error'])) {
                break;
            }
            sleep(1);
        }

        if (!is_array($result) || isset($result['error'])) {
            $errorMessage = is_array($result) && isset($result['error']) ? $result['error'] : 'Authentification incorrecte';
            $request->session()->put('status', $errorMessage);
            return back()->withErrors(['message' => $errorMessage]);
        }

        $token = $result['token'];

        // persist token in session via the service and set it for future calls
        $cisco->setToken($token);

        // Ensure a local Laravel user exists to satisfy Laravel's auth middleware
        $user = User::where('name', $username)->first();
        if (! $user) {
            $user = User::create([
                'name' => $username,
                'email' => $username . '@local.netlab',
                'password' => bcrypt(\Illuminate\Support\Str::random(40)),
            ]);
        }

        Auth::login($user);

        return redirect()->intended(route('dashboard', absolute: false));
    }



    /**
     * Destroy an authenticated session.
     */
    public function destroy(User $user, Request $request): RedirectResponse
    {
        $ciscoService = app(\App\Services\CiscoApiService::class);

        // Try to revoke CML token via service if available
        try {
            if (session('cml_token')) {
                $ciscoService->logout(session('cml_token'));
            }
            $ciscoService->revokeToken();
        } catch (\Exception $e) {
            // Log the error but don't interrupt logout process
            Log::warning('Failed to revoke CML token during logout: ' . $e->getMessage());
        }


        Auth::logout($user);

        // Logout Laravel user
        Auth::guard('web')->logout();

        // Clean session data
        $request->session()->invalidate();
        $request->session()->forget('cml_token');
        $request->session()->regenerateToken();

        // Ensure complete session destruction
        $request->session()->flush();

        return redirect('/');
    }
}
