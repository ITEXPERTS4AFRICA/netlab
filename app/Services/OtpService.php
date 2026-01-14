<?php

namespace App\Services;

use App\Models\OtpCode;

class OtpService
{
    // Changement int -> string ici
    public function generate(string $userId): OtpCode
    {
        return OtpCode::create([
            'user_id' => $userId,
            'code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    // Changement int -> string ici
    public function verify(string $userId, string $code): bool
    {
        $otp = OtpCode::where('user_id', $userId)
            ->where('code', $code)
            ->where('used', false)
            ->latest()
            ->first();

        if (! $otp || now()->greaterThan($otp->expires_at)) {
            return false;
        }

        $otp->update(['used' => true]);

        return true;
    }

    // Changement int -> string ici
    public function invalidatePrevious(string $userId): void
    {
        OtpCode::where('user_id', $userId)
            ->where('used', false)
            ->update(['used' => true]);
    }
}
