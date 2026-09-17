<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Post\PostResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => "{$this->firstName} {$this->lastName}",
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'image' => $this->whenLoaded('image', function () {
                return [
                    'image_url' => $this->image->image_url,
                    'public_id' => $this->image->public_id,
                    'order'     => $this->image->order,
                ];
            }),
            'posts' => $this->whenLoaded('posts', function () {
                return PostResource::collection($this->posts);
            }),
            'status' => $this->status,
            'error_login_count' => $this->error_login_count,
            'last_login' => $this->last_login,
            'random_token' => $this->random_token,
            'last_change_password' => $this->last_change_password,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
