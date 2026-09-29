<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // only author admin and super admin can access this
    public function getAllUsers(Request $request)
    {
        try {
            // $id = $request->attributes->get('userId');
            // $user = User::find($id);

            $user = $request->attributes->get('user');

            return response()->json([
                'message'       => 'All users.',
                "currentUserId" => $user->id,
                "currentUserRole" => $user->role,
            ], 200);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error'      => 'Error while fetching all users ',
                'message'    => $e->getMessage(),
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }
}
