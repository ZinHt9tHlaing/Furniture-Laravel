<?php

namespace App\Services\Cloudinary;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;

class CloudinaryFileUploadService
{
    /**
     * Upload API instance
     * @var UploadApi
     */
    protected readonly UploadApi $uploadApi;

    /**
     * constructor to initialize the upload API
     */
    public function __construct()
    {
        // configure Cloudinary
        $config = new Configuration();
        $config->cloud->cloudName = config('services.cloudinary.cloud_name');
        $config->cloud->apiKey    = config('services.cloudinary.api_key');
        $config->cloud->apiSecret = config('services.cloudinary.api_secret');
        $config->url->secure     = true;

        // create upload API instance
        $this->uploadApi = new UploadApi($config);
    }

    /**
     * Upload file to Cloudinary
     * @param UploadedFile $file
     * @param string $folderName
     */
    public function upload(string|UploadedFile $file, string $folderName = "uploads")
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        // upload the file
        $result = $this->uploadApi->upload($filePath, [
            "folder" => $folderName,
            "resource_type" => "image", // image, video, audio, ...
            'transformation' => [
                'width' => 835,
                'height' => 577,
                'crop' => 'limit',
                'quality' => 'auto',
                'fetch_format' => 'auto', // automatically choose best format
            ],
        ]);

        // return the secure URL of the uploaded file
        return [
            "image_url" => $result['secure_url'],
            "public_id" => $result['public_id'],
        ];
    }

    /**
     * Delete uploaded file from Cloudinary
     * @param string $publicId
     */
    public function delete(string $publicId)
    {
        return $this->uploadApi->destroy($publicId);
    }
}
