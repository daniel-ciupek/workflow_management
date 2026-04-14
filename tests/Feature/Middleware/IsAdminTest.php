<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_employee_session_without_auth_is_redirected_to_login(): void
    {
        // Employee uses session (not Laravel auth) — 'auth' middleware redirects to login
        $this->withSession(['employee_access' => true])
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }
}
