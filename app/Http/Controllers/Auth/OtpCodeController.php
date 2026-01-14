<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\InfobipWhatsAppService;
use Inertia\Inertia;



class OtpCodeController extends Controller
{
    public function create(){
        return Inertia::render('auth/verify-otp');
    }



    public function verify(
        VerifyOtpRequest $request,
        OtpService $otpService
    ) {
        $userId = session('otp_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        if (! $otpService->verify($userId, $request->code)) {
            return back()->withErrors([
                'code' => 'Code OTP invalide ou expiré.',
            ]);
        }

        $user = User::findOrFail($userId);
        $user->update(['is_active' => true]);

        Auth::login($user);
        session()->forget('otp_user_id');

        return redirect()->route('dashboard');
    }


    public function resend(
        OtpService $otpService,
        InfobipWhatsAppService $whatsApp
    ) {
        $userId = session('otp_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        // 🔐 Rate limit : 3 OTP / 10 min
        if (RateLimiter::tooManyAttempts('otp-resend:' . $userId, 3)) {
            throw ValidationException::withMessages([
                'code' => 'Trop de demandes. Veuillez patienter quelques minutes.',
            ]);
        }

        RateLimiter::hit('otp-resend:' . $userId, 600);

        $user = User::findOrFail($userId);

        // Invalider les anciens OTP
        $otpService->invalidatePrevious($user->id);

        // Générer et envoyer le nouveau
        $otp = $otpService->generate($user->id);
        $whatsApp->sendOtp($user->phone, $otp->code);

        return back()->with('success', 'Un nouveau code a été envoyé sur WhatsApp.');
    }
}
