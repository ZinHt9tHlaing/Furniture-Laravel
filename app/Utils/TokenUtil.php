<?php

namespace App\Utils;

use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

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

    /**
     * Generate Access Token & Refresh Token for a user
     * @param User $user
     */
    public static function generateAuthTokens(User $user): array
    {
        $accessTokenSecret = config('services.custom_secret_key.access_token_secret') ?: 'access_token';
        $refreshTokenSecret = config('services.custom_secret_key.refresh_token_secret') ?: 'refresh_token';

        $accessToken = $user->createToken(
            $accessTokenSecret,
            ['id' => $user->id],
            now()->addMinutes(15) // expires in 15 minutes
        )->plainTextToken;

        $refreshToken = $user->createToken(
            $refreshTokenSecret,
            [
                'id'    => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            now()->addDays(30) // expires in 30 days
        )->plainTextToken;

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Create Auth Cookie
     * @param string $name
     * @param string $tokenValue
     * @param int $expireTime
     * @return Cookie
     */
    public static function createAuthCookie(string $name, string $tokenValue, int $expireTime): Cookie
    {
        // Check if the app is running in production
        $isProduction = app()->isProduction();
        $sameSite     = $isProduction ? 'none' : 'strict';

        return cookie(
            name: $name, // cookie name
            value: $tokenValue, // token value
            minutes: $expireTime, // expires time
            path: '/', // cookie path
            domain: null, // cookie domain
            secure: $isProduction, // Secure (only works on HTTPS)
            httpOnly: true, // HttpOnly (Cannot be used from JavaScript.)
            sameSite: $sameSite // SameSite (controls when cookies are sent with cross-site requests)
        );
    }
}
