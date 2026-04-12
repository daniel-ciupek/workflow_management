<?php

namespace Tests\Feature\Admin;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TaskHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_only_own_archived_tasks(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $myTask    = Task::factory()->archived()->create(['created_by' => $admin1->id, 'title' => 'My Archived']);
        $otherTask = Task::factory()->archived()->create(['created_by' => $admin2->id, 'title' => 'Other Archived']);

        $this->actingAs($admin1);

        Volt::test('admin.task-history')
            ->assertSee('My Archived')
            ->assertDontSee('Other Archived');
    }

    public function test_admin_does_not_see_active_tasks_in_history(): void
    {
        $admin      = User::factory()->admin()->create();
        $activeTask = Task::factory()->create(['created_by' => $admin->id, 'title' => 'Active Task']);

        $this->actingAs($admin);

        Volt::test('admin.task-history')
            ->assertDontSee('Active Task');
    }

    public function test_admin_can_delete_archived_task(): void
    {
        $admin = User::factory()->admin()->create();
        $task  = Task::factory()->archived()->create(['created_by' => $admin->id]);

        $this->actingAs($admin);

        Volt::test('admin.task-history')
            ->call('confirmDelete', $task->id)
            ->call('destroy');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
