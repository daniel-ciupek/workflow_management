<?php

namespace Tests\Feature\Employee;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_done_sets_pivot_done_and_completed_at(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();
        $task     = Task::factory()->create(['created_by' => $admin->id]);
        $task->users()->attach($employee->id, ['done' => false]);

        $this->withSession(['employee_access' => true, 'employee_id' => $employee->id]);

        Volt::test('employee.dashboard')
            ->call('markDone', $task->id);

        $pivot = $employee->tasks()->wherePivot('task_id', $task->id)->first()->pivot;
        $this->assertTrue((bool) $pivot->done);
        $this->assertNotNull($pivot->completed_at);
    }

    public function test_mark_done_without_session_employee_id_does_nothing(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();
        $task     = Task::factory()->create(['created_by' => $admin->id]);
        $task->users()->attach($employee->id, ['done' => false]);

        // No employee_id in session
        $this->withSession(['employee_access' => true]);

        Volt::test('employee.dashboard')
            ->call('markDone', $task->id);

        $pivot = $employee->tasks()->wherePivot('task_id', $task->id)->first()->pivot;
        $this->assertFalse((bool) $pivot->done);
    }

    public function test_dashboard_shows_only_active_tasks(): void
    {
        $admin       = User::factory()->admin()->create();
        $employee    = User::factory()->employee()->create();
        $activeTask  = Task::factory()->create(['created_by' => $admin->id]);
        $archivedTask = Task::factory()->archived()->create(['created_by' => $admin->id]);
        $activeTask->users()->attach($employee->id);
        $archivedTask->users()->attach($employee->id);

        $this->withSession(['employee_access' => true, 'employee_id' => $employee->id]);

        Volt::test('employee.dashboard')
            ->assertSee($activeTask->title)
            ->assertDontSee($archivedTask->title);
    }
}
