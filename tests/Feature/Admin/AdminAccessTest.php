<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    // --- Super admin vs regular admin — route access ---

    public function test_super_admin_can_access_admins_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $this->get(route('admin.admins'))->assertOk();
    }

    public function test_regular_admin_gets_403_on_admins_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Volt::test('admin.admins')->assertForbidden();
    }

    // --- Employee list isolation ---

    public function test_regular_admin_sees_only_own_employees(): void
    {
        $admin1    = User::factory()->admin()->create();
        $admin2    = User::factory()->admin()->create();
        $employee1 = User::factory()->employee()->create(['name' => 'Alice']);
        $employee2 = User::factory()->employee()->create(['name' => 'Bob']);

        $admin1->employees()->attach($employee1->id);
        $admin2->employees()->attach($employee2->id);

        $this->actingAs($admin1);

        Volt::test('admin.employees')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    public function test_super_admin_sees_all_employees(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin      = User::factory()->admin()->create();
        $employee1  = User::factory()->employee()->create(['name' => 'Alice']);
        $employee2  = User::factory()->employee()->create(['name' => 'Bob']);

        $admin->employees()->attach($employee1->id);
        $superAdmin->employees()->attach($employee2->id);

        $this->actingAs($superAdmin);

        Volt::test('admin.employees')
            ->assertSee('Alice')
            ->assertSee('Bob');
    }

    // --- Super admin CRUD on admins ---

    public function test_super_admin_can_create_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        Volt::test('admin.admins')
            ->set('name', 'New Admin')
            ->set('pin', '777777')
            ->set('pin_confirmation', '777777')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['name' => 'New Admin', 'role' => 'admin']);
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        Volt::test('admin.admins')
            ->call('confirmDelete', $superAdmin->id);

        // deletingId should remain null (guard in confirmDelete)
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_super_admin_can_delete_other_admin(): void
    {
        $superAdmin  = User::factory()->superAdmin()->create();
        $otherAdmin  = User::factory()->admin()->create();
        $this->actingAs($superAdmin);

        Volt::test('admin.admins')
            ->call('confirmDelete', $otherAdmin->id)
            ->set('deletingId', $otherAdmin->id)
            ->call('destroy');

        $this->assertDatabaseMissing('users', ['id' => $otherAdmin->id]);
    }
}
