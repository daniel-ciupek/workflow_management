<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'     => fake()->name(),
            'pin'      => null,
            'role'     => 'employee',
            'is_super' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role'     => 'admin',
            'pin'      => (string) fake()->unique()->numerify('1#####'),
            'is_super' => false,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'role'     => 'admin',
            'pin'      => '000000',
            'is_super' => true,
        ]);
    }

    public function employee(): static
    {
        return $this->state(fn () => [
            'role' => 'employee',
            'pin'  => null,
        ]);
    }
}
