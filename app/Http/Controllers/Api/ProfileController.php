<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use App\Utils\AuthorizeUtil;
use App\Utils\AuthUtil;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function testPermission(Request $request)
    {
        $userId = $request->attributes->get('userId');
        $user = AuthService::getUserById($userId);

        AuthUtil::checkUserIfNotExist($user);

        $info = [
            "title" => "Testing permission",
        ];

        // if user->role === "AUTHOR"
        // content = "You are an author."

        $canAuthorize = AuthorizeUtil::isAuthorized(true, $user->role->value, "ADMIN");
        if ($canAuthorize) {
            $info['content'] = "You have permission to read this line.";
        } else {
            $info['content'] = "You don't have permission to read this line.";
        }

        return response()->json([
            "currentUserRole" => $user->role,
            "info" => $info,
        ], 200);
    }
}
