<?php

namespace App\Http\Controllers\Api;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UploadProfileRequest;
use App\Jobs\UploadProfileJob;
use App\Services\Auth\AuthService;
use App\Utils\AuthorizeUtil;
use App\Utils\AuthUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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


    public function uploadProfile(UploadProfileRequest $request)
    {
        try {
            $userId = $request->attributes->get('userId');
            $user = $request->user() ?: AuthService::getUserById($userId);

            AuthUtil::checkUserIfNotExist($user);

            // store file in temp directory, /private/temp_profiles
            $tempPath = $request->file('avatar')->store('temp_profiles', 'local');

            // get old public id for deletion
            $oldPublicId = $user->image?->public_id;

            // dispatch job for profile upload
            UploadProfileJob::dispatch($user, $tempPath, $oldPublicId);

            return response()->json([
                'message' => 'Profile uploaded successfully.',
            ], 200);
        } catch (ApiException $e) {
            throw $e; // run render method of ApiException automatically
        } catch (\Exception $e) {
            Log::error('Error while dispatching profile upload: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'message'    => 'Error while uploading profile',
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }
}
