<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $admin = User::factory()->admin()->make();
        $this->assertTrue($admin->isAdmin());
    }

    public function test_is_admin_returns_false_for_employee_role(): void
    {
        $employee = User::factory()->employee()->make();
        $this->assertFalse($employee->isAdmin());
    }

    public function test_is_super_admin_returns_true_when_admin_and_is_super(): void
    {
        $super = User::factory()->superAdmin()->make();
        $this->assertTrue($super->isSuperAdmin());
    }

    public function test_is_super_admin_returns_false_for_regular_admin(): void
    {
        $admin = User::factory()->admin()->make(['is_super' => false]);
        $this->assertFalse($admin->isSuperAdmin());
    }

    public function test_is_employee_returns_true_for_employee_role(): void
    {
        $employee = User::factory()->employee()->make();
        $this->assertTrue($employee->isEmployee());
    }

    public function test_employees_relationship_returns_assigned_employees(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();

        $admin->employees()->attach($employee->id);

        $this->assertTrue($admin->employees->contains($employee));
    }

    public function test_admins_relationship_returns_admins_for_employee(): void
    {
        $admin    = User::factory()->admin()->create();
        $employee = User::factory()->employee()->create();

        $admin->employees()->attach($employee->id);

        $this->assertTrue($employee->admins->contains($admin));
    }
}
