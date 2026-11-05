<?php

declare(strict_types=1);

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_expected_roles_permissions_and_assignments(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(5, Role::count());
        $this->assertSame(23, Permission::count());
        $this->assertSame(49, DB::table('role_has_permissions')->count());
        $this->assertSame(0, User::count());

        $expectedCounts = [
            'Super Admin' => 23,
            'Manager' => 15,
            'Dispatcher' => 4,
            'Accountant' => 5,
            'Delivery Personnel' => 2,
        ];

        foreach ($expectedCounts as $roleName => $expectedCount) {
            $role = Role::findByName($roleName, Permissions::GUARD);

            $this->assertSame(
                $expectedCount,
                $role->permissions()->count(),
                "Incorrect permission count for {$roleName}."
            );
        }
    }

    public function test_each_role_has_exactly_its_approved_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        foreach (Permissions::roles() as $roleName) {
            $role = Role::findByName($roleName, Permissions::GUARD);

            $actualPermissions = $role->permissions
                ->pluck('name')
                ->sort()
                ->values()
                ->all();

            $expectedPermissions = collect(Permissions::forRole($roleName))
                ->sort()
                ->values()
                ->all();

            $this->assertSame(
                $expectedPermissions,
                $actualPermissions,
                "Permission assignments do not match the approved configuration for {$roleName}."
            );
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(5, Role::count());
        $this->assertSame(23, Permission::count());
        $this->assertSame(49, DB::table('role_has_permissions')->count());
        $this->assertSame(0, User::count());
    }

    public function test_super_admin_has_all_permissions_and_delivery_personnel_has_only_own_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $superAdmin = Role::findByName(
            Permissions::ROLE_SUPER_ADMIN,
            Permissions::GUARD
        );

        $superAdminPermissions = $superAdmin->permissions
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $allPermissions = collect(Permissions::all())
            ->sort()
            ->values()
            ->all();

        $this->assertSame($allPermissions, $superAdminPermissions);

        $deliveryPersonnel = Role::findByName(
            Permissions::ROLE_DELIVERY_PERSONNEL,
            Permissions::GUARD
        );

        $deliveryPermissions = $deliveryPersonnel->permissions
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $expectedDeliveryPermissions = collect(
            Permissions::forRole(Permissions::ROLE_DELIVERY_PERSONNEL)
        )->sort()->values()->all();

        $this->assertSame(
            $expectedDeliveryPermissions,
            $deliveryPermissions
        );

        $this->assertCount(2, $deliveryPermissions);
    }

    public function test_user_without_permissions_is_not_granted_permission_automatically(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();

        $this->assertFalse($user->can('dashboard.view'));
        $this->assertFalse($user->can('shipments.create'));
        $this->assertFalse($user->can('settings.manage'));
    }
}
