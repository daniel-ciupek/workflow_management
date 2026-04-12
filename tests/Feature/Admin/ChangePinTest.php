<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ChangePinTest extends TestCase
{
    use RefreshDatabase;

    // --- Admin PIN change ---

    public function test_admin_can_change_own_pin(): void
    {
        $admin = User::factory()->admin()->create(['pin' => '111111']);
        $this->actingAs($admin);

        Volt::test('admin.change-pin')
            ->set('current_pin', '111111')
            ->set('new_pin', '222222')
            ->set('confirm_pin', '222222')
            ->call('saveAdminPin')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('222222', $admin->fresh()->pin));
    }

    public function test_admin_pin_change_fails_with_wrong_current_pin(): void
    {
        $admin = User::factory()->admin()->create(['pin' => '111111']);
        $this->actingAs($admin);

        Volt::test('admin.change-pin')
            ->set('current_pin', '999999')
            ->set('new_pin', '222222')
            ->set('confirm_pin', '222222')
            ->call('saveAdminPin')
            ->assertHasErrors(['current_pin']);

        $this->assertTrue(Hash::check('111111', $admin->fresh()->pin));
    }

    public function test_admin_pin_change_fails_if_new_pin_not_6_digits(): void
    {
        $admin = User::factory()->admin()->create(['pin' => '111111']);
        $this->actingAs($admin);

        Volt::test('admin.change-pin')
            ->set('current_pin', '111111')
            ->set('new_pin', '123')
            ->set('confirm_pin', '123')
            ->call('saveAdminPin')
            ->assertHasErrors(['new_pin']);
    }

    public function test_admin_pin_change_fails_if_confirmation_does_not_match(): void
    {
        $admin = User::factory()->admin()->create(['pin' => '111111']);
        $this->actingAs($admin);

        Volt::test('admin.change-pin')
            ->set('current_pin', '111111')
            ->set('new_pin', '222222')
            ->set('confirm_pin', '333333')
            ->call('saveAdminPin')
            ->assertHasErrors(['confirm_pin']);
    }

    public function test_admin_pin_change_fails_if_new_pin_same_as_current(): void
    {
        $admin = User::factory()->admin()->create(['pin' => '111111']);
        $this->actingAs($admin);

        Volt::test('admin.change-pin')
            ->set('current_pin', '111111')
            ->set('new_pin', '111111')
            ->set('confirm_pin', '111111')
            ->call('saveAdminPin')
            ->assertHasErrors(['new_pin']);
    }

    // --- Employee PIN change ---

    public function test_admin_can_change_employee_pin(): void
    {
        $admin = User::factory()->admin()->create();
        Setting::set('employee_pin', '1234');
        $this->actingAs($admin);

        Volt::test('admin.change-pin')
            ->set('new_employee_pin', '5678')
            ->set('confirm_employee_pin', '5678')
            ->call('saveEmployeePin')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('5678', Setting::get('employee_pin')));
    }

    public function test_employee_pin_change_fails_if_not_4_digits(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Volt::test('admin.change-pin')
            ->set('new_employee_pin', '12')
            ->set('confirm_employee_pin', '12')
            ->call('saveEmployeePin')
            ->assertHasErrors(['new_employee_pin']);
    }

    public function test_employee_pin_change_fails_if_confirmation_does_not_match(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Volt::test('admin.change-pin')
            ->set('new_employee_pin', '1234')
            ->set('confirm_employee_pin', '5678')
            ->call('saveEmployeePin')
            ->assertHasErrors(['confirm_employee_pin']);
    }
}
