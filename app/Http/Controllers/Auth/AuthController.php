<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ErrorCode;
use App\Enums\Status;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RegisterWithEmailRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\Auth\AuthService;
use App\Utils\AuthUtil;
use App\Utils\TokenUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

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

    public function confirmPassword(ConfirmPasswordRequest $request)
    {
        try {
            ['phone' => $phone, 'password' => $password, 'token' => $token] = $request->validated();

            $user = AuthService::getUserByPhone($phone);
            AuthUtil::checkUserExists($user);

            $otpRow = AuthService::getOtpByPhone($phone);
            AuthUtil::checkOtpIfNotExist($otpRow);

            if ($otpRow->error === 5) {
                throw new ApiException(
                    "This request may be an attack.",
                    400,
                    ErrorCode::BadRequest
                );
            }

            if ($otpRow->verify_token !== $token) {
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

            // request is expired
            /** @var Carbon $updatedAt */
            $updatedAt = $otpRow->updated_at;
            $isRequestExpired = $updatedAt->addMinutes(10)->isPast();
            if ($isRequestExpired) {
                throw new ApiException(
                    "Your request is expired. Please register again.",
                    403,
                    ErrorCode::RequestExpired
                );
            }

            $hashedPassword = Hash::make($password);
            $randomToken = "I will replace Refresh Token soon";

            $userData = [
                'phone'        => $phone,
                'password'     => $hashedPassword,
                'random_token' => $randomToken,
            ];

            $newUser = AuthService::createUser($userData);

            // Generate Access Token & Refresh Token
            $tokens = TokenUtil::generateAuthTokens($newUser);

            $userUpdateData = [
                'random_token' => $tokens['refresh_token'],
            ];

            AuthService::updateUser($newUser->id, $userUpdateData);

            // create cookies
            $accessCookie = TokenUtil::createAuthCookie('accessToken', $tokens['access_token'], 15); // 15 Minutes
            $refreshCookie = TokenUtil::createAuthCookie('refreshToken', $tokens['refresh_token'], 30 * 24 * 60); // 30 days

            return response()->json([
                'message' => 'Your account is successfully created.',
                "userId" => $newUser->id,
            ], 201)
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error while confirming password: ',
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }

    public function registerWithEmail(RegisterWithEmailRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            [$newUser, $tokens] = DB::transaction(function () use ($validated) {
                $phone = $validated['phone'];
                if (Str::startsWith($phone, "09")) {
                    $phone = Str::after($phone, '09'); // Remove the "09"
                }

                $hashedPassword = Hash::make($validated['password']);
                $randomToken = "I will replace Refresh Token soon";

                $userData = [
                    'firstName'    => $validated['firstName'],
                    'lastName'     => $validated['lastName'],
                    'email'        => $validated['email'],
                    'phone'        => $phone,
                    'password'     => $hashedPassword,
                    'random_token' => $randomToken,
                ];

                $newUser = AuthService::createUser($userData);

                // Generate Access Token & Refresh Token
                $tokens = TokenUtil::generateAuthTokens($newUser);

                // Update random_token with refresh token
                $newUser->update([
                    'random_token' => $tokens['refresh_token'],
                ]);

                return [$newUser, $tokens];
            });

            // Create cookies
            $accessCookie  = TokenUtil::createAuthCookie('accessToken', $tokens['access_token'], 15); // 15 Minutes
            $refreshCookie = TokenUtil::createAuthCookie('refreshToken', $tokens['refresh_token'], 30 * 24 * 60); // 30 days

            return response()->json([
                'message' => 'Your account is successfully created.',
                'userId'  => $newUser->id,
            ], 201)
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error while creating user: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'message'    => 'Error while creating user: ' . $e->getMessage(),
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $phone = $validated['phone'];
            if (Str::startsWith($phone, "09")) {
                $phone = Str::after($phone, '09'); // Remove "09"
            }

            $user = AuthService::getUserByPhone($phone);
            AuthUtil::checkUserIfNotExist($user);

            // Check if account is frozen
            if ($user->status === Status::FREEZE) {
                throw new ApiException(
                    "Your account is temporarily locked. Please contact us.",
                    401,
                    ErrorCode::AccountFreeze
                );
            }

            $isMatchPassword = Hash::check($validated['password'], $user->password);
            if (!$isMatchPassword) {
                // Check if last attempt was today using updated_at
                $lastRequestDate = Carbon::parse($user->updated_at);
                $today = Carbon::now();
                $isSameDate = $lastRequestDate->isSameDay($today);

                if (!$isSameDate) {
                    // First wrong attempt today
                    AuthService::updateUser($user->id, [
                        "error_login_count" => 1,
                    ]);
                } else {
                    if ($user->error_login_count >= 2) {
                        // Reached 3rd wrong attempt today -> freeze account
                        AuthService::updateUser($user->id, [
                            "status" => Status::FREEZE->value,
                        ]);
                    } else {
                        // Increment failed count
                        AuthService::updateUser($user->id, [
                            "error_login_count" => $user->error_login_count + 1,
                        ]);
                    }
                }

                throw new ApiException(
                    "Invalid Credentials!",
                    401,
                    ErrorCode::Invalid
                );
            }

            // Password is correct -> Reset error count & generate tokens
            $tokens = TokenUtil::generateAuthTokens($user);

            AuthService::updateUser($user->id, [
                "error_login_count" => 0, // reset error count
                "last_login"        => Carbon::now(), // update last login time
                "random_token"      => $tokens['refresh_token'], // update random token
            ]);

            // Create cookies
            $accessCookie  = TokenUtil::createAuthCookie('accessToken', $tokens['access_token'], 15);
            $refreshCookie = TokenUtil::createAuthCookie('refreshToken', $tokens['refresh_token'], 30 * 24 * 60);

            return response()->json([
                'message'      => 'Successfully Logged In.',
                'userId'       => $user->id,
                'token' => $tokens['access_token'],
            ], 200)
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error'      => 'Error while logging in user: ',
                'message'    => $e->getMessage(),
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $refreshToken = $request->hasCookie("refreshToken") ? $request->cookie("refreshToken") : null;
            if (!$refreshToken) {
                throw new ApiException(
                    "You are not an authenticated user.",
                    401,
                    ErrorCode::Unauthenticated
                );
            }

            $tokenModel = PersonalAccessToken::findToken($refreshToken);
            if (!$tokenModel) {
                throw new ApiException(
                    "You are not an authenticated user.",
                    401,
                    ErrorCode::Unauthenticated
                );
            }

            $tokenOwner = $tokenModel->tokenable; // User Model Object

            $user = AuthService::getUserById($tokenOwner->id);
            AuthUtil::checkUserIfNotExist($user);

            if ($user->phone !== $tokenOwner->phone) {
                throw new ApiException(
                    "You are not an authenticated user.",
                    401,
                    ErrorCode::Unauthenticated
                );
            }

            // To ensure the random_token cannot be reused after logging out.
            $userData = [
                'random_token' => TokenUtil::generateToken(),
            ];
            AuthService::updateUser($user->id, $userData);

            return response()->json([
                'message' => 'Successfully Logged out.',
            ], 200)
                ->withoutCookie('accessToken')
                ->withoutCookie('refreshToken');
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error'      => 'Error while logging out user: ',
                'message'    => $e->getMessage(),
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }
}
