<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Observers\TaskObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_removes_attachments_from_disk(): void
    {
        Storage::fake('tasks');

        $admin = User::factory()->admin()->create();
        $task  = Task::factory()->create([
            'created_by'  => $admin->id,
            'attachments' => ["task-1/file.jpg"],
        ]);

        Storage::disk('tasks')->put("task-1/file.jpg", 'content');

        $task->delete();

        Storage::disk('tasks')->assertMissing("task-1/file.jpg");
    }

    public function test_deleting_task_without_attachments_does_not_throw(): void
    {
        Storage::fake('tasks');

        $admin = User::factory()->admin()->create();
        $task  = Task::factory()->create(['created_by' => $admin->id, 'attachments' => null]);

        $task->delete();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_prune_does_not_remove_when_completed_count_is_at_or_below_30(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();

        for ($i = 0; $i < 30; $i++) {
            $task = Task::factory()->create(['created_by' => $admin->id]);
            $employee->tasks()->attach($task->id, ['done' => true, 'completed_at' => now()]);
        }

        TaskObserver::pruneCompletedForUser($employee);

        $this->assertCount(30, $employee->tasks()->wherePivot('done', true)->get());
    }

    public function test_prune_removes_oldest_tasks_beyond_30(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();

        for ($i = 0; $i < 32; $i++) {
            $task = Task::factory()->create(['created_by' => $admin->id]);
            $employee->tasks()->attach($task->id, [
                'done'         => true,
                'completed_at' => now()->subMinutes(32 - $i),
            ]);
        }

        TaskObserver::pruneCompletedForUser($employee);

        $this->assertCount(30, $employee->tasks()->wherePivot('done', true)->get());
    }

    public function test_prune_deletes_task_if_no_other_users_assigned(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();

        $oldTasks = [];
        for ($i = 0; $i < 31; $i++) {
            $task = Task::factory()->create(['created_by' => $admin->id]);
            $employee->tasks()->attach($task->id, [
                'done'         => true,
                'completed_at' => now()->subMinutes(31 - $i),
            ]);
            $oldTasks[] = $task->id;
        }

        TaskObserver::pruneCompletedForUser($employee);

        // The oldest task (first created) should be deleted since it had no other users
        $this->assertDatabaseMissing('tasks', ['id' => $oldTasks[0]]);
    }
}
