<?php

namespace Database\Factories;

use App\Models\ProductOnOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOnOrder>
 */
class ProductOnOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quantity' => $this->faker->numberBetween(1, 10),
            'price'    => $this->faker->randomFloat(2, 50, 2000),
        ];
    }
}
