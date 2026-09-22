<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getAllUsers(Request $request)
    {
        try {
            $id = $request->attributes->get('userId');
            $user = User::find($id);

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
