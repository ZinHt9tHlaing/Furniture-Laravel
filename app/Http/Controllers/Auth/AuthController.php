<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Utils\AuthUtil;
use App\Utils\TokenUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $phone = $request->input('phone');
            if (Str::startsWith($phone, "09")) {
                $phone = Str::after($phone, '09'); // Remove the "09"
            }

            $user  = AuthService::getUserByPhone($phone);
            AuthUtil::checkUserExists($user);

            // Generate OTP & call OTP sending API
            // if sms OTP cannot be sent, response error
            // Save OTP in DB

            $otp = 123456; // For testing
            // $otp = TokenUtil::generateOtp(); // for production
            $hashedOtp = Hash::make($otp);

            $token = TokenUtil::generateToken();

            $otpRow = AuthService::getOtpByPhone($phone);
            $result = null;

            if (!$otpRow) {
                $optData = [
                    "phone" => $phone,
                    "otp" => $hashedOtp,
                    "remember_token" => $token,
                    "count" => 1,
                ];

                $result = AuthService::createOtp($optData);
            } else {
                $lastOtpRequestDate = Carbon::parse($otpRow->updated_at);
                $today = Carbon::now();

                // Check if the user has made 5 wrong OTP verification attempts in the same day
                $isSameDate = $lastOtpRequestDate->isSameDay($today);
                AuthUtil::checkOtpErrorIfSameDate($isSameDate, $otpRow->error);

                if (!$isSameDate) {
                    $optData = [
                        "otp" => $hashedOtp,
                        "remember_token" => $token,
                        "count" => 1,
                        "error" => 0
                    ];

                    $result = AuthService::updateOtp($otpRow->id, $optData);
                } else {
                    if ($otpRow->count === 3) {
                        throw new ApiException(
                            "OTP is allowed to request 3 times per day.",
                            405,
                            ErrorCode::OverLimit
                        );
                    } else {
                        $otpData = [
                            "otp" => $hashedOtp,
                            "remember_token" => $token,
                            "count" => $otpRow->count + 1,
                        ];
                        $result = AuthService::updateOtp($otpRow->id, $otpData);
                    }
                }
            }

            return response()->json([
                'message'      => "OTP has been sent to 09{$result->phone}",
                'phone'        => $result->phone,
                'token'          => $result->remember_token,
            ], 201);
        } catch (ApiException $e) {
            throw $e; // run render method of ApiException automatically
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error while registering user: ',
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }

    /**
     * Verify user OTP
     * The phone number and token will be automatically provided by the system.
     * If more than two minutes have passed since the register API was called, OTP verification is considered expired.
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            ['phone' => $phone, 'otp' => $otp, 'token' => $token] = $request->validated();

            $user = AuthService::getUserByPhone($phone);
            AuthUtil::checkUserExists($user);

            $otpRow = AuthService::getOtpByPhone($phone);
            AuthUtil::checkOtpIfNotExist($otpRow);

            $isSameDate = Carbon::parse($otpRow->updated_at)->isSameDay(Carbon::now());
            AuthUtil::checkOtpErrorIfSameDate($isSameDate, $otpRow->error);

            // Token is wrong, may be under attack
            if ($otpRow->remember_token !== $token) {
                $optData = [
                    "error" => 5
                ];
                AuthService::updateOtp($otpRow->id, $optData);

                throw new ApiException(
                    "Invalid token.",
                    400, // Bad request
                    ErrorCode::Invalid
                );
            }

            // OTP is expired after more than 2 minutes
            /** @var Carbon $updatedAt */
            $updatedAt = $otpRow->updated_at;
            $isOtpExpired = $updatedAt->addMinutes(2)->isPast();
            if ($isOtpExpired) {
                throw new ApiException(
                    "OTP is expired.",
                    403,
                    ErrorCode::OtpExpired
                );
            }

            // OTP is wrong
            $isMatchOTP  = Hash::check($otp, $otpRow->otp);
            if (!$isMatchOTP) {
                // If OTP error is first time today
                if (!$isSameDate) {
                    $optData = [
                        "error" => 1
                    ];
                    AuthService::updateOtp($otpRow->id, $optData);
                } else {
                    // If OTP error is not first time today
                    $optData = [
                        "error" => $otpRow->error + 1
                    ];
                    AuthService::updateOtp($otpRow->id, $optData);

                    throw new ApiException(
                        "OTP is incorrect.",
                        401,
                        ErrorCode::Invalid
                    );
                }
            }

            // All are OK
            $verifyToken = TokenUtil::generateToken();
            $optData = [
                'verify_token' => $verifyToken,
                'count' => 1,
                'error' => 0,
            ];

            $result = AuthService::updateOtp($otpRow->id, $optData);

            return response()->json([
                'message'      => "OTP is successfully verified.",
                'phone'        => $result->phone,
                'token'          => $result->verify_token,
            ], 201);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error while verifying OTP: ',
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'    => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            /** @var User $user */
            $user  = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message'      => 'Login successful.',
                'user'         => $user,
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error while logging in user: ',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
