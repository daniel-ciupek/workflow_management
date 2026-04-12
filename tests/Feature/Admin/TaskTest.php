<?php

namespace Tests\Feature\Admin;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    // --- Task creation ---

    public function test_admin_can_create_task_with_title_and_employee(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();
        $admin->employees()->attach($employee->id);

        $this->actingAs($admin);

        Volt::test('admin.task-form')
            ->set('title', 'Fix roof')
            ->set('selectedEmployees', [$employee->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.tasks'));

        $this->assertDatabaseHas('tasks', ['title' => 'Fix roof', 'created_by' => $admin->id]);
        $task = Task::where('title', 'Fix roof')->first();
        $this->assertDatabaseHas('task_user', ['task_id' => $task->id, 'user_id' => $employee->id]);
    }

    public function test_create_task_fails_without_title(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();
        $admin->employees()->attach($employee->id);

        $this->actingAs($admin);

        Volt::test('admin.task-form')
            ->set('title', '')
            ->set('selectedEmployees', [$employee->id])
            ->call('save')
            ->assertHasErrors(['title']);
    }

    public function test_create_task_fails_without_employees(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Volt::test('admin.task-form')
            ->set('title', 'Fix roof')
            ->set('selectedEmployees', [])
            ->call('save')
            ->assertHasErrors(['selectedEmployees']);
    }

    // --- Task deletion ---

    public function test_admin_can_delete_own_task(): void
    {
        Storage::fake('tasks');

        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();
        $task     = Task::factory()->create(['created_by' => $admin->id]);
        $task->users()->attach($employee->id);

        $this->actingAs($admin);

        Volt::test('admin.tasks')
            ->call('confirmDelete', $task->id)
            ->call('destroy');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    // --- Archival scopes ---

    public function test_active_task_is_not_shown_in_archived_scope(): void
    {
        $task = Task::factory()->create(['archived_at' => null]);
        $this->assertNull(Task::archived()->find($task->id));
    }

    public function test_archived_task_is_shown_in_archived_scope(): void
    {
        $task = Task::factory()->archived()->create();
        $this->assertNotNull(Task::archived()->find($task->id));
    }

    public function test_archived_task_is_not_shown_in_active_scope(): void
    {
        $task = Task::factory()->archived()->create();
        $this->assertNull(Task::active()->find($task->id));
    }

    // --- Edit task ---

    public function test_admin_can_edit_task_title(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();
        $admin->employees()->attach($employee->id);
        $task = Task::factory()->create(['created_by' => $admin->id]);
        $task->users()->attach($employee->id);

        $this->actingAs($admin);

        Volt::test('admin.tasks')
            ->call('openEdit', $task->id)
            ->set('editTitle', 'Updated Title')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Updated Title']);
    }
}
