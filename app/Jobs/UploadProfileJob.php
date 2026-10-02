<?php

namespace App\Jobs;

use App\Enums\ErrorCode;
use App\Models\User;
use App\Services\Cloudinary\CloudinaryFileUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UploadProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3; // 3 attempts
    public int $timeout = 120; // 2 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $tempFilePath,
        public ?string $oldPublicId = null
    ) {
        // make this job run in 'profile' queue
        $this->onQueue('profile');
    }

    /**
     * Execute the job.
     */
    public function handle(CloudinaryFileUploadService $cloudinary)
    {
        // Get absolute path to the temp file, /storage/app/private/temp_profiles
        $absolutePath = Storage::disk('local')->path($this->tempFilePath);

        if (!file_exists($absolutePath)) {
            Log::error("Profile temp file not found: {$this->tempFilePath} for User ID: {$this->user->id}");

            return response()->json([
                'message'    => "Profile temp file not found: {$this->tempFilePath} for User ID: {$this->user->id}",
                'error_code' => ErrorCode::InternalError->value,
            ], 500);
        }

        try {
            // Upload new profile picture to Cloudinary
            $uploaded = $cloudinary->upload($absolutePath, 'furniture/profile');

            DB::transaction(function () use ($uploaded) {
                // Update or Create image in the database
                $this->user->image()->updateOrCreate(
                    [
                        'imageable_id'   => $this->user->id,
                        'imageable_type' => User::class,
                    ],
                    [
                        "image_url" => $uploaded["image_url"],
                        "public_id" => $uploaded["public_id"],
                    ]
                );
            });

            if ($this->oldPublicId) {
                try {
                    $cloudinary->delete($this->oldPublicId);
                } catch (Throwable $e) {
                    Log::warning("Failed to delete old Cloudinary image: {$this->oldPublicId}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (Throwable $th) {
            throw $th;
        } finally {
            // delete temp file if exist
            if (Storage::disk('local')->exists($this->tempFilePath)) {
                Storage::disk('local')->delete($this->tempFilePath);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception)
    {
        Log::error("Profile image upload failed for User ID: {$this->user->id}", [
            'error' => $exception->getMessage(),
        ]);

        // delete temp file if exist
        if (Storage::disk('local')->exists($this->tempFilePath)) {
            Storage::disk('local')->delete($this->tempFilePath);
        }

        return response()->json([
            'message'    => "Profile image upload failed for User ID: {$this->user->id}",
            'error_code' => ErrorCode::InternalError->value,
        ], 500);
    }
}
