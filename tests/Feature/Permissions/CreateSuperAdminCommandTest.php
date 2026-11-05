<?php

declare(strict_types=1);

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_super_admin_successfully(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->artisan('trackyp:super-admin:create')
            ->expectsQuestion('Administrator full name', 'John Administrator')
            ->expectsQuestion('Administrator email address', 'admin@trackyp.test')
            ->expectsQuestion('Phone number (optional)', '08012345678')
            ->expectsQuestion('Enter administrator password', 'StrongPassword#2026')
            ->expectsQuestion('Confirm administrator password', 'StrongPassword#2026')
            ->assertExitCode(0);

        $user = User::where('email', 'admin@trackyp.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('John Administrator', $user->name);
        $this->assertSame('08012345678', $user->phone);
        $this->assertTrue($user->hasRole(Permissions::ROLE_SUPER_ADMIN));
        $this->assertTrue($user->hasPermissionTo('settings.manage'));
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check(
                'StrongPassword#2026',
                $user->password
            )
        );
    }

    public function test_it_prevents_creating_a_second_super_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $existingAdmin = User::factory()->create([
            'email' => 'existing@trackyp.test',
        ]);

        $existingAdmin->assignRole(Permissions::ROLE_SUPER_ADMIN);

        $this->artisan('trackyp:super-admin:create')
            ->expectsOutput('A Super Admin already exists for this installation.')
            ->assertExitCode(1);

        $this->assertSame(1, User::count());
    }

    public function test_it_rejects_a_weak_password(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->artisan('trackyp:super-admin:create')
            ->expectsQuestion('Administrator full name', 'John Administrator')
            ->expectsQuestion('Administrator email address', 'admin@trackyp.test')
            ->expectsQuestion('Phone number (optional)', '')
            ->expectsQuestion('Enter administrator password', 'weak')
            ->expectsQuestion('Confirm administrator password', 'weak')
            ->expectsOutput('The password does not meet the security requirements.')
            ->assertExitCode(1);

        $this->assertSame(0, User::count());
    }

    public function test_it_rejects_mismatched_passwords(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->artisan('trackyp:super-admin:create')
            ->expectsQuestion('Administrator full name', 'John Administrator')
            ->expectsQuestion('Administrator email address', 'admin@trackyp.test')
            ->expectsQuestion('Phone number (optional)', '')
            ->expectsQuestion('Enter administrator password', 'StrongPassword#2026')
            ->expectsQuestion('Confirm administrator password', 'DifferentPassword#2026')
            ->expectsOutput('The passwords do not match.')
            ->assertExitCode(1);

        $this->assertSame(0, User::count());
    }

    public function test_it_refuses_to_create_an_admin_when_the_super_admin_role_is_missing(): void
    {
        $this->artisan('trackyp:super-admin:create')
            ->expectsOutput('The Super Admin role does not exist.')
            ->assertExitCode(1);

        $this->assertSame(0, User::count());
    }
}
