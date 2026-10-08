<?php

namespace App\Http\Controllers\Api;

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UploadProfileRequest;
use App\Http\Resources\User\UserResource;
use App\Jobs\UploadProfileJob;
use App\Services\Auth\AuthService;
use App\Utils\AuthorizeUtil;
use App\Utils\AuthUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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

    public function getMyPhoto(Request $request)
    {
        try {
            $userId = $request->attributes->get('userId');
            $user = $request->user() ?: AuthService::getUserById($userId);

            AuthUtil::checkUserIfNotExist($user);

            return response()->json([
                "message" => "Successfully got my photo",
                "image_url" => $user?->image?->image_url,
                "public_id" => $user?->image?->public_id,
            ], 200);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("Error while getting my photo: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                "message" => "Error while getting my photo",
                "error_code" => ErrorCode::InternalError->value,
            ], 500);
        }
    }

    /**
     * Get user info
     */
    public function getUserInfo(Request $request)
    {
        try {
            $userId = $request->attributes->get('userId');
            $user = $request->user() ?: AuthService::getUserById($userId);

            AuthUtil::checkUserIfNotExist($user);

            $user->loadMissing('image');

            return response()->json([
                "userInfo" => new UserResource($user)
            ], 200);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error'      => 'Error while getting user info',
                'message'    => $e->getMessage(),
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }

    public function changeName(Request $request)
    {
        $validated = $request->validate([
            'firstName' => ['required', 'string', 'max:52'],
            'lastName'  => ['required', 'string', 'max:52'],
        ]);

        try {
            $userId = $request->attributes->get('userId');
            $user = $request->user() ?: AuthService::getUserById($userId);

            AuthUtil::checkUserIfNotExist($user);

            $user->update([
                'firstName' => $validated['firstName'],
                'lastName' => $validated['lastName'],
            ]);

            AuthUtil::checkUserIfNotExist($user);

            return response()->json([
                "message" => "Profile name updated successfully",
            ], 200);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error'      => 'Failed to update profile name',
                'message'    => $e->getMessage(),
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }

    public function changeEmail(Request $request)
    {
        $validated = $request->validate([
            'email'     => ['required', 'string', 'email', 'max:52', Rule::unique('users', 'email')],
        ]);

        try {
            $userId = $request->attributes->get('userId');
            $user = $request->user() ?: AuthService::getUserById($userId);

            AuthUtil::checkUserIfNotExist($user);

            $existingEmail = AuthService::getUserByEmail($validated['email']);
            if ($existingEmail) {
                return response()->json([
                    "message" => "Email already exists",
                ], 409);
            }

            $user->update([
                'email' => $validated['email'],
            ]);

            AuthUtil::checkUserIfNotExist($user);

            return response()->json([
                "message" => "Email updated successfully",
            ], 200);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error'      => 'Failed to update profile email',
                'message'    => $e->getMessage(),
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'currentPassword' => ['required', 'string', 'min:6'],
            // 'newPassword'     => ['required', 'string', 'min:6', 'different:currentPassword'],
            'newPassword'     => ['required', 'string', 'min:6'],
            'confirmPassword' => ['required', 'same:newPassword'],
        ]);

        try {
            $userId = $request->attributes->get('userId');
            $user = $request->user() ?: AuthService::getUserById($userId);

            AuthUtil::checkUserIfNotExist($user);

            if (!Hash::check($validated['currentPassword'], $user->password)) {
                return response()->json([
                    'message' => 'Current password does not match.',
                ], 422);
            }

            $user->update([
                'password' => Hash::make($validated['newPassword']),
                'last_change_password' => Carbon::now(),
            ]);

            AuthUtil::checkUserIfNotExist($user);

            return response()->json([
                "message" => "Successfully changed password",
            ], 200);
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error'      => 'Failed to update profile password',
                'message'    => $e->getMessage(),
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }
    }
}
