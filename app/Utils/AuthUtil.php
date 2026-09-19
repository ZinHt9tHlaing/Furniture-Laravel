<?php

namespace App\Utils;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Models\User;

class AuthUtil
{
    public static function checkUserExists(?User $user)
    {
        if ($user) {
            throw new ApiException(
                "This phone number has already been registered.",
                409,
                ErrorCode::UserExist
            );
        }
    }

    public static function checkOtpErrorIfSameDate(bool $isSameDate, int $errorCount)
    {
        // Check if the user has made 5 wrong attempts in the same day
        if ($isSameDate && $errorCount >= 5) {
            throw new ApiException(
                "OTP is wrong for 5 times. Please try again tomorrow.",
                401,
                ErrorCode::OverLimit
            );
        }
    }
}
