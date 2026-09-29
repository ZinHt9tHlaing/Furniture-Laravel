<?php

namespace App\Http\Middleware\Auth;

use App\Enums\ErrorCode;
use App\Services\Auth\AuthService;
use App\Utils\AuthUtil;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeMiddleware
{
    /**
     * Usage in routes:
     * ->middleware('authorize:true,ADMIN,AUTHOR')
     * ->middleware('authorize:false,USER')
     *
     * @param Request $request
     * @param Closure $next
     * @param bool $permission
     * @param string ...$roles
     * @return Response
     */
    public function handle(Request $request, Closure $next, bool $permission, string ...$roles): Response
    {
        $userId = $request->attributes->get('userId');
        $user = AuthService::getUserById($userId);

        AuthUtil::checkUserIfNotExist($user);

        // 'true' => allow list, 'false' => block list
        $allowed = $permission === 'true';
        // Check if user has role
        $hasRole = in_array($user->role->value ?? $user->role, $roles);

        // If allowed and user does not have role or not allowed and user has role
        if (($allowed && !$hasRole) || (!$allowed && $hasRole)) {
            return response()->json([
                'message'    => 'This action is not allowed!',
                'error_code' => ErrorCode::Unauthorized,
            ], 403);
        }

        $request->setUserResolver(fn() => $user); // Enables $request->user()
        $request->attributes->set('user', $user);  // Alternative: $request->attributes->get('user')

        return $next($request);
    }
}
