<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_returns_only_tasks_without_archived_at(): void
    {
        Task::factory()->create(['archived_at' => null]);
        Task::factory()->create(['archived_at' => null]);
        Task::factory()->archived()->create();

        $this->assertCount(2, Task::active()->get());
    }

    public function test_archived_scope_returns_only_tasks_with_archived_at(): void
    {
        Task::factory()->create(['archived_at' => null]);
        Task::factory()->archived()->create();
        Task::factory()->archived()->create();

        $this->assertCount(2, Task::archived()->get());
    }

    public function test_creator_relationship_returns_the_admin_who_created_task(): void
    {
        $admin = User::factory()->admin()->create();
        $task  = Task::factory()->create(['created_by' => $admin->id]);

        $this->assertTrue($task->creator->is($admin));
    }

    public function test_users_relationship_has_done_and_completed_at_pivots(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();
        $task     = Task::factory()->create(['created_by' => $admin->id]);

        $task->users()->attach($employee->id, [
            'done'         => true,
            'completed_at' => now(),
        ]);

        $pivot = $task->users()->first()->pivot;

        $this->assertTrue((bool) $pivot->done);
        $this->assertNotNull($pivot->completed_at);
    }
}
