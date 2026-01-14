<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\RegisterUserRequest;
use App\Services\InfobipWhatsAppService;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterUserRequest $request, OtpService $otpService, InfobipWhatsAppService $whatsApp): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:student,teacher,admin',
            'phone' => ['string', 'max:20'],
            'organization' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);


        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'password_confirmation' => Hash::make($validated['password']),
            'role' => 'student',
            'is_active' => true,
            'phone' => $validated['phone'] ,
            'organization' => $validated['organization'] ?? null,
            'department' => $validated['department'] ?? null,
            'position' => $validated['position'] ?? null,
        ]);

        Auth::login($user);

        if($user->phone){
            try {
                $otp = $otpService->generate($user->id);
                $whatsApp->sendOtp($user->phone, $otp->code);

                session(['otp_user_id'=>$user->id]);

                return redirect()
                    ->route("otp.verify.form")
                    ->with("sucess','Un code OTP à été envoyé sur votre WhatsApp.");

            } catch(\Throwable $e){
                logger()->error($e->getMessage());
                    // Auth::logout();
                    $user->delete();
                return back()->withErrors([
                    'phone' => "Impossible d'envoyer le code de vérification."
                ]);
            }
        }

        event(new Registered($user));


        return redirect()->intended(route('login', absolute: false));
    }
}
