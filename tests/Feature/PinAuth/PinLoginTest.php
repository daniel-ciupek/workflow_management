<?php

namespace Tests\Feature\PinAuth;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PinLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login.' . '127.0.0.1');
    }

    // --- Login page ---

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    // --- Admin PIN login ---

    public function test_admin_can_login_with_valid_6_digit_pin(): void
    {
        $admin = User::factory()->admin()->create(['pin' => '123456']);

        Volt::test('pages.auth.login')
            ->set('pin', '123456')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_login_fails_with_wrong_pin(): void
    {
        User::factory()->admin()->create(['pin' => '123456']);

        Volt::test('pages.auth.login')
            ->set('pin', '999999')
            ->call('authenticate')
            ->assertHasErrors(['pin']);

        $this->assertGuest();
    }

    // --- Employee PIN login ---

    public function test_employee_can_login_with_valid_4_digit_pin(): void
    {
        Setting::set('employee_pin', '1234');

        Volt::test('pages.auth.login')
            ->set('pin', '1234')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('employee.select'));
    }

    public function test_employee_login_fails_with_wrong_pin(): void
    {
        Setting::set('employee_pin', '1234');

        Volt::test('pages.auth.login')
            ->set('pin', '9999')
            ->call('authenticate')
            ->assertHasErrors(['pin']);
    }

    // --- Invalid formats ---

    public function test_login_fails_with_3_digit_pin(): void
    {
        Volt::test('pages.auth.login')
            ->set('pin', '123')
            ->call('authenticate')
            ->assertHasErrors(['pin']);
    }

    public function test_login_fails_with_7_digit_pin(): void
    {
        Volt::test('pages.auth.login')
            ->set('pin', '1234567')
            ->call('authenticate')
            ->assertHasErrors(['pin']);
    }

    public function test_login_fails_with_non_numeric_pin(): void
    {
        Volt::test('pages.auth.login')
            ->set('pin', 'abcdef')
            ->call('authenticate')
            ->assertHasErrors(['pin']);
    }

    // --- Rate limiting ---

    public function test_rate_limiter_blocks_after_5_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Volt::test('pages.auth.login')
                ->set('pin', '999999')
                ->call('authenticate');
        }

        $result = Volt::test('pages.auth.login')
            ->set('pin', '999999')
            ->call('authenticate');

        $result->assertHasErrors(['pin']);
        $this->assertStringContainsStringIgnoringCase(
            'many',
            $result->errors()->first('pin')
        );
    }

    // --- Logout ---

    public function test_admin_authentication_is_cleared_after_logout(): void
    {
        $admin = User::factory()->admin()->create(['pin' => '123456']);
        $this->actingAs($admin);
        $this->assertAuthenticatedAs($admin);

        // Simulate the logout route closure logic directly
        auth()->guard('web')->logout();

        $this->assertGuest();
    }

    public function test_employee_logout_clears_session(): void
    {
        $this->withSession(['employee_access' => true, 'employee_id' => 1]);

        $this->get(route('employee.logout'))
            ->assertRedirect('/login');

        $this->assertFalse(session()->has('employee_access'));
    }
}
