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
        // Validation (si RegisterUserRequest ne le fait pas déjà)
        $validated = $request->validated();

        // Création de l'utilisateur
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),

            'role' => $validated['role'], // Tu avais 'role' dans la validation mais forcé 'student' ici
            'is_active' => false, // IMPORTANT : Inactif tant que l'OTP n'est pas validé
            'phone' => $validated['phone'],
            'organization' => $validated['organization'] ?? null,
            'department' => $validated['department'] ?? null,
            'position' => $validated['position'] ?? null,
        ]);

        // if ($user->phone) {
        //     try {
        //         $otpService->invalidatePrevious($user->id);




        //         $otp = $otpService->generate($user->id);

        //         // Nettoyage du numéro avant envoi
        //         $cleanPhone = preg_replace('/[^0-9+]/', '', $user->phone);

        //         $envoye = $whatsApp->sendOtp($cleanPhone, $otp->code);

        //         // Le service lève maintenant une exception si $envoye est false, 
        //         // donc ce test ci-dessous n'est plus strictement nécessaire pour l'erreur,
        //         // mais on peut le garder pour la logique.
        //         if ($envoye) {
        //             session(['otp_user_id' => $user->id]);
        //             return redirect()->route('otp.verify.form')->with('success', 'Code envoyé.');
        //         }
        //     } catch (\Throwable $e) {
        //         logger()->error("OTP Error: " . $e->getMessage());
        //         $user->delete();

        //         // Affiche le message d'erreur d'Infobip si disponible, sinon le message générique
        //         $errorMessage = "Impossible d'envoyer le code. " . $e->getMessage();

        //         return back()->withErrors([
        //             'phone' => $errorMessage
        //         ])->withInput();
        //     }
        // }

        // Fallback si pas de téléphone (optionnel selon ta logique métier)
        // return redirect()->route('login')->with('error', 'Un numéro de téléphone est requis.');

        return redirect()->route('login')->with('success', 'inscript');
    }
}
