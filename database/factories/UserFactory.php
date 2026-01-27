<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Infrastructure\Persistence\Eloquent\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = \App\Infrastructure\Persistence\Eloquent\Models\User::class;
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
            'name' => fake()->name(),
            'cpf' => fake()->unique()->numerify('###########'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'type' => UserType::COMMON,
        ];
    }

    /**
     * Indicate that the user is a common user.
     */
    public function common(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserType::COMMON,
        ]);
    }

    /**
     * Indicate that the user is a shopkeeper.
     */
    public function shopkeeper(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserType::SHOPKEEPER,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
