<?php

namespace App\Http\Middleware\Auth;

use App\Enums\ErrorCode;
use App\Models\User;
use App\Utils\TokenUtil;
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
        $platform = $request->header('x-platform');
        $isMobile = $platform === 'mobile';

        $accessToken = $isMobile
            ? $request->bearerToken()
            : $request->cookie('accessToken');

        $refreshToken = $isMobile
            ? $request->header('x-refresh-token') // custom header for mobile
            : $request->cookie('refreshToken');

        // Handle mobile platform
        if ($isMobile) {
            return $this->handleMobileAuth($request, $next, $accessToken);
        }

        if (!$refreshToken) {
            return $this->unauthenticatedResponse('You are not an authenticated user.');
        }

        if (!$accessToken) {
            return $this->generateNewTokens($request, $next, $refreshToken);
            //  return response()->json([
            //     'message' => 'Access Token has expired.',
            //     'error_code' => ErrorCode::AccessTokenExpired
            // ], 401);
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

        // Update token usage time
        $tokenModel->forceFill(['last_used_at' => now()])->save();

        // Set user id to request
        $request->attributes->set('userId', $user->id);

        return $next($request);
    }

    /**
     * Validates refresh token, issues new tokens, attaches cookies to downstream response.
     */
    protected function generateNewTokens(Request $request, Closure $next, string $refreshToken): Response
    {
        $user = User::where('random_token', $refreshToken)->first();

        if (!$user) {
            return $this->unauthenticatedResponse('You are not an authenticated user.');
        }

        $tokens = TokenUtil::generateAuthTokens($user);

        // Bind user to request pipeline
        $request->attributes->set('userId', $user->id);
        auth()->guard()->setUser($user);

        $response = $next($request);

        $newAccessCookie  = TokenUtil::createAuthCookie('accessToken', $tokens['access_token'], 15); // 15 minutes
        $newRefreshCookie = TokenUtil::createAuthCookie('refreshToken', $tokens['refresh_token'], 30 * 24 * 60); // 30 days

        return $response
            ->withCookie($newAccessCookie)
            ->withCookie($newRefreshCookie);
    }

    /**
     * Handle mobile bearer token validation.
     */
    protected function handleMobileAuth(Request $request, Closure $next, ?string $accessToken): Response
    {
        if (!$accessToken) {
            return response()->json([
                'message' => 'Access Token is missing.',
                'error_code' => ErrorCode::AccessTokenExpired
            ], 401);
        }

        $tokenModel = PersonalAccessToken::findToken($accessToken);
        if (!$tokenModel || ($tokenModel->expires_at && $tokenModel->expires_at->isPast())) {
            return response()->json([
                'message'    => 'Access token has expired.',
                'error_code' => ErrorCode::AccessTokenExpired,
            ], 401);
        }

        $user = $tokenModel->tokenable;
        if (!$user) {
            return $this->unauthenticatedResponse('This account has not registered.');
        }

        // Update token usage time
        $tokenModel->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('userId', $user->id);
        auth()->guard()->setUser($user);

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
