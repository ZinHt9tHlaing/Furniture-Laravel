<?php

namespace App\Services\Auth;

use App\Models\OTP;
use App\Models\User;

class AuthService
{
    public static function getUserByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public static function createOtp(array $optData): ?OTP
    {
        return OTP::create($optData);
    }

    public static function getOtpByPhone(string $phone): ?OTP
    {
        return OTP::where('phone', $phone)->first();
    }

    public static function updateOtp(string $id, array $otpData): ?OTP
    {
        $otp = OTP::find($id);
        if ($otp) {
            $otp->update($otpData);
        }
        return $otp;
    }
}
