<?php

namespace App\Http\Middleware\Auth;

use App\Enums\ErrorCode;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AttachTokenFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the token from the Authorization header or cookie
        // $tokenString = $request->bearerToken()?? $request->input('refresh_token')?? $request->cookie('refreshToken');

        $accessToken = $request->hasCookie("accessToken") ? $request->cookie("accessToken") : null;
        $refreshToken = $request->hasCookie("refreshToken") ? $request->cookie("refreshToken") : null;

        if (!$refreshToken) {
            return $this->unauthenticatedResponse('You are not an authenticated user.');
        }

        if (!$accessToken) {
            return response()->json([
                'message' => 'Access Token has expired.',
                'error_code' => ErrorCode::AccessTokenExpired
            ], 401);
        }

        // Verify access token
        $tokenModel = PersonalAccessToken::findToken($accessToken);
        if (!$tokenModel) {
            return $this->unauthenticatedResponse('You are not an authenticated user.');
        }

        if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
            return response()->json([
                'message'    => 'Access token is expired.',
                'error_code' => ErrorCode::AccessTokenExpired,
            ], 401);
        }

        $user = $tokenModel->tokenable; // User Model Object
        if (!$user) {
            return $this->unauthenticatedResponse('This account has not registered.');
        }

        // Set user id to request
        $request->attributes->set('userId', $user->id);

        return $next($request);
    }

    /**
     * Return Unauthenticated Response
     * @param string $message
     * @return Response
     */
    protected function unauthenticatedResponse(string $message): Response
    {
        return response()->json([
            'message'    => $message,
            'error_code' => ErrorCode::Unauthenticated,
        ], 401);
    }
}
