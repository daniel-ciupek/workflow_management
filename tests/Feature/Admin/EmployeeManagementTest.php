<?php

namespace Tests\Feature\Admin;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    // --- Create employee ---

    public function test_admin_can_create_employee(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Volt::test('admin.employees')
            ->set('name', 'John Doe')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['name' => 'John Doe', 'role' => 'employee']);
    }

    public function test_create_employee_fails_without_name(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Volt::test('admin.employees')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_super_admin_requires_at_least_one_admin_assigned(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        Volt::test('admin.employees')
            ->set('name', 'No Admin Employee')
            ->set('adminIds', [])
            ->call('save')
            ->assertHasErrors(['adminIds']);
    }

    // --- Edit employee ---

    public function test_admin_can_edit_employee_name(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create(['name' => 'Old Name']);
        $admin->employees()->attach($employee->id);

        $this->actingAs($admin);

        Volt::test('admin.employees')
            ->call('openEdit', $employee->id)
            ->set('name', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $employee->id, 'name' => 'New Name']);
    }

    // --- Delete employee ---

    public function test_admin_can_delete_employee(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();
        $admin->employees()->attach($employee->id);

        $this->actingAs($admin);

        Volt::test('admin.employees')
            ->call('confirmDelete', $employee->id)
            ->call('destroy');

        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
    }

    public function test_deleting_employee_removes_task_user_pivot_entries(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();
        $task     = Task::factory()->create(['created_by' => $admin->id]);
        $task->users()->attach($employee->id);
        $admin->employees()->attach($employee->id);

        $this->actingAs($admin);

        Volt::test('admin.employees')
            ->call('confirmDelete', $employee->id)
            ->call('destroy');

        $this->assertDatabaseMissing('task_user', ['user_id' => $employee->id]);
    }

    // --- Admin assignment rules ---

    public function test_regular_admin_is_always_assigned_to_created_employee(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Volt::test('admin.employees')
            ->set('name', 'My Employee')
            ->call('save')
            ->assertHasNoErrors();

        $employee = User::where('name', 'My Employee')->first();
        $this->assertTrue($employee->admins()->where('users.id', $admin->id)->exists());
    }

    public function test_super_admin_can_assign_any_admin_to_employee(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin      = User::factory()->admin()->create();
        $this->actingAs($superAdmin);

        Volt::test('admin.employees')
            ->set('name', 'Shared Employee')
            ->set('adminIds', [(string) $admin->id])
            ->call('save')
            ->assertHasNoErrors();

        $employee = User::where('name', 'Shared Employee')->first();
        $this->assertTrue($employee->admins()->where('users.id', $admin->id)->exists());
    }
}
