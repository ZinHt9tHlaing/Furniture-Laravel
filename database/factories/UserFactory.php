<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'firstName' => substr(fake()->firstName(), 0, 52),
            'lastName' => substr(fake()->lastName(), 0, 52),
            'phone' => substr(fake()->unique()->numerify('09#########'), 0, 15),
            'email' => substr(fake()->unique()->safeEmail(), 0, 52),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(Role::cases()),
            'status' => fake()->randomElement(Status::cases()),
            'last_login' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'error_login_count' => fake()->numberBetween(0, 3),
            'random_token' => Str::random(32),
            'last_change_password' => fake()->optional()->dateTimeBetween('-6 months', 'now'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
