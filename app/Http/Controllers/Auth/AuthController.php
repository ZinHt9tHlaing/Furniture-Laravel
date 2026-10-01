<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ErrorCode;
use App\Enums\Status;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use App\Utils\AuthUtil;
use App\Utils\TokenUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
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

            // Find Sanctum token
            $tokenOwner = $tokenModel->tokenable; // User Model Object
            if (!$tokenModel || !$tokenModel->tokenable) {
                throw new ApiException(
                    'You are not an authenticated user.',
                    401,
                    ErrorCode::Unauthenticated
                );
            }

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

            // Revoke the current refresh token from Sanctum
            $tokenModel->delete();

            // Expire cookies explicitly on root path
            $cookieAccessToken = cookie()->forget('accessToken', '/');
            $cookieRefreshToken = cookie()->forget('refreshToken', '/');

            return response()->json([
                'message' => 'Successfully Logged out.',
            ], 200)
                ->withCookie($cookieAccessToken)
                ->withCookie($cookieRefreshToken);
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

    /**
     * Refresh Token Rotation
     */
    public function setRefreshToken(Request $request)
    {
        try {
            $refreshTokenString = $request->bearerToken()
                ?? $request->cookie("refreshToken")
                ?? $request->input("refreshToken");

            if (!$refreshTokenString) {
                throw new ApiException(
                    "You are not an authenticated user.",
                    401,
                    ErrorCode::Unauthenticated
                );
            }

            $tokenModel = PersonalAccessToken::findToken($refreshTokenString);
            if (!$tokenModel) {
                throw new ApiException(
                    "You are not an authenticated user.",
                    401,
                    ErrorCode::Unauthenticated
                );
            }

            // Check if the token has expired
            if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
                $tokenModel->delete();

                return response()->json([
                    "message"    => "Refresh token has expired.",
                    "error_code" => ErrorCode::TokenExpired,
                ], 401)
                    ->withCookie(cookie()->forget('accessToken', '/'))
                    ->withCookie(cookie()->forget('refreshToken', '/'));
            }

            $user = $tokenModel->tokenable; // User Model Object
            if (!$user) {
                throw new ApiException(
                    "You are not an authenticated user.",
                    401,
                    ErrorCode::Unauthenticated
                );
            }

            // Check if the user account is frozen
            if ($user->status === Status::FREEZE) {
                throw new ApiException(
                    'Your account is temporarily locked. Please contact us.',
                    401,
                    ErrorCode::AccountFreeze
                );
            }

            return DB::transaction(function () use ($user, $tokenModel, $refreshTokenString) {
                // Check if the random token in database matches
                if ($user->random_token && $user->random_token !== $refreshTokenString) {
                    throw new ApiException(
                        "You are not an authenticated user.",
                        401,
                        ErrorCode::Unauthenticated
                    );
                }

                // Revoke the current refresh token from Sanctum
                $tokenModel->delete();

                // Generate new tokens
                $tokens = TokenUtil::generateAuthTokens($user);

                // Update random_token
                $userData = [
                    'random_token' => $tokens['refresh_token'],
                ];
                AuthService::updateUser($user->id, $userData);

                // Create new cookies
                $newAccessCookie  = TokenUtil::createAuthCookie('accessToken', $tokens['access_token'], 15); // 15 minutes
                $newRefreshCookie = TokenUtil::createAuthCookie('refreshToken', $tokens['refresh_token'], 30 * 24 * 60); // 30 days

                return response()->json([
                    'message'      => 'Token refreshed successfully.',
                    'access_token' => $tokens['access_token'],
                ], 200)
                    ->withCookie($newAccessCookie)
                    ->withCookie($newRefreshCookie);
            });
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error'      => 'Error while refreshing token: ',
                'message'    => $e->getMessage(),
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }
}
