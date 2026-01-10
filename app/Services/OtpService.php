<?php

namespace App\Services;

use App\Models\OtpCode;

class OtpService
{
    public function generate(int $userId): OtpCode
    {
        return OtpCode::create([
            'user_id' => $userId,
            'code' => random_int(100000, 999999),
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    public function verify(int $userId, string $code): bool {
        $otp = OtpCode::where('user_id', $userId)
            ->where('code', $code)
            ->where('used',false)
            ->latest()
            ->first();

        if (! $otp || now()->greaterThan($otp->expires_at)){
            return false;
        }

        $otp->update(['used' => true]);

        return true;
    }

    public function invalidatePrevious(int $userId): void
    {
        OtpCode::where('user_id', $userId)
            ->where('used', false)
            ->update(['used' => true]);
    }
}
