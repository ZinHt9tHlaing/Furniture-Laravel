<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\Status;
use App\Models\Category;
use App\Models\Image;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Type;
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
        Category::factory(4)->create();
        Type::factory(4)->create();
        Tag::factory(10)->create();

        // Create 5 products to have a decent pool for orders
        $products = Product::factory(5)->create();

        // Create 3 orders, each linked to a random existing user (or new)
        Order::factory(3)->create()->each(function (Order $order) use ($products) {
            // Pick 1–4 random products for this order
            $selectedProducts = $products->random(rand(1, 5));

            $pivotData = [];
            $totalPrice = 0;

            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 5);
                $price    = round($product->price * (1 - $product->discount / 100), 2);

                $pivotData[$product->id] = [
                    'quantity' => $quantity,
                    'price'    => $price,
                ];

                $totalPrice += $quantity * $price;
            }

            // Attach products to order through pivot table
            $order->products()->attach($pivotData);

            // Update total_price to reflect actual items
            $order->update(['total_price' => round($totalPrice, 2)]);
        });

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
                    ->create(['author_id' => $author->id])
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
