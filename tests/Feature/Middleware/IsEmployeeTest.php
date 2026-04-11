<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsEmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_with_session_can_access_employee_dashboard(): void
    {
        $employee = User::factory()->employee()->create();

        $this->withSession(['employee_access' => true, 'employee_id' => $employee->id])
            ->get(route('employee.dashboard'))
            ->assertOk();
    }

    public function test_request_without_employee_session_is_redirected_to_login(): void
    {
        $this->get(route('employee.dashboard'))
            ->assertRedirect('/login');
    }

    public function test_authenticated_admin_without_employee_session_is_redirected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('employee.dashboard'))
            ->assertRedirect('/login');
    }
}
