<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'     => fake()->sentence(6),
            'content'   => fake()->paragraph(2),
            'body'      => fake()->paragraphs(5, true),
            'authorId'  => User::factory(),
        ];
    }
}
