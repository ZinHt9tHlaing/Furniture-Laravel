<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Image;
use App\Models\Post;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User with Avatar
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'firstName'            => 'Admin',
                'lastName'             => 'User',
                'phone'                => '09123456789',
                'password'             => Hash::make('password'),
                'role'                 => Role::ADMIN,
                'status'               => Status::ACTIVE,
                'random_token'         => Str::random(32),
                'email_verified_at'    => now(),
                'last_change_password' => now(),
            ]
        );

        $admin->image()->create([
            'image_url' => 'https://ui-avatars.com/api/?name=Admin+User',
            'public_id' => 'avatars/' . Str::random(20),
            'order'     => 0,
        ]);

        // Authors who create Posts with polymorphic Images
        User::factory(5)
            ->state(['role' => Role::AUTHOR])
            ->create()
            ->each(function (User $author) {
                // Single polymorphic avatar for author
                $author->image()->save(Image::factory()->make(['order' => 0]));

                // 3 to 6 posts per author
                Post::factory(rand(3, 6))
                    ->create(['authorId' => $author->id])
                    ->each(function (Post $post) {
                        // 1 to 3 ordered polymorphic images per post
                        $imagesCount = rand(1, 3);
                        for ($order = 0; $order < $imagesCount; $order++) {
                            $post->images()->save(
                                Image::factory()->make(['order' => $order])
                            );
                        }
                    });
            });

        // Regular users without posts
        // User::factory(5)
        //     ->state(['role' => Role::USER])
        //     ->create()
        //     ->each(function (User $user) {
        //         $user->image()->save(Image::factory()->make(['order' => 0]));
        //     });
    }
}
