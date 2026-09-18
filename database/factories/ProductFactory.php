<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'        => $this->faker->name(),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->numberBetween(100, 1000),
            'discount'    => $this->faker->numberBetween(0, 50),
            'rating'      => $this->faker->numberBetween(0, 5),
            'inventory'   => $this->faker->numberBetween(0, 100),
            'status'      => fake()->randomElement(Status::cases()),
            'category_id' => Category::factory(),
            'type_id'     => Type::factory(),
        ];
    }
}
