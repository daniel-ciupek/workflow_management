<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by'  => User::factory()->admin(),
            'title'       => fake()->sentence(3),
            'address'     => null,
            'materials'   => null,
            'description' => null,
            'attachments' => null,
            'archived_at' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'archived_at' => now()->subHours(25),
        ]);
    }

    public function old(): static
    {
        return $this->state(fn () => [
            'created_at' => now()->subHours(25),
        ]);
    }
}
