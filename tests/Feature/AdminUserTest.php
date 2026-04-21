<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_view_users_index(): void
    {
        $admin = User::factory()->create(['name' => 'Admin']);
        $admin->assignRole('ADMIN');
        $user = User::factory()->create(['name' => 'Agent test']);

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertViewHas('users')
            ->assertSee($user->name);
    }

    public function test_non_admin_cannot_view_users_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('users.index'))
            ->assertForbidden();
    }
}
