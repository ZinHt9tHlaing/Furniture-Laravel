<?php

namespace App\Utils;

use Illuminate\Support\Str;

class TokenUtil
{
    /**
     * Generate 6-digit cryptographically secure OTP
     */
    public static function generateOtp(int $digits = 6): string
    {
        $min = 10 ** ($digits - 1);
        $max = (10 ** $digits) - 1;

        return (string) random_int($min, $max);
    }

    /**
     * Generate 64-character secure random hex token
     */
    public static function generateToken(int $length = 64): string
    {
        return Str::random($length);

        // To generate a 256-bit (32-byte) random binary string
        // return bin2hex(random_bytes(32));
    }
}
