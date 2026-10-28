<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Tests only: in production the hub writes users. @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => User::STATUS_ACTIVE,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => User::STATUS_SUSPENDED]);
    }

    /** The HOP owner. */
    public function owner(): static
    {
        return $this->afterCreating(fn (User $u) => $u->roles()->create(['role' => Role::OWNER]));
    }

    /** A HOP admin (every app), or admin of one app when $app is given. */
    public function admin(?string $app = null): static
    {
        return $this->afterCreating(fn (User $u) => $u->roles()->create(['role' => Role::ADMIN, 'app_slug' => $app]));
    }
}
