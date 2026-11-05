<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Registers TrackYP's roles and permissions and links them together.
 *
 * Everything comes from App\Support\Permissions (the single source of
 * truth). The seeder is idempotent: running it repeatedly creates no
 * duplicates and simply re-applies the approved assignments.
 *
 * It never creates users and never assigns roles to users.
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        // Start from a clean cache so we never read stale permission data.
        $registrar->forgetCachedPermissions();

        // 1. Register every permission in the catalogue.
        foreach (Permissions::all() as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => Permissions::GUARD,
            ]);
        }

        // 2. Register every role and set its exact permissions.
        foreach (Permissions::roles() as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => Permissions::GUARD,
            ]);

            // Replace existing assignments with the approved list.
            $role->syncPermissions(Permissions::forRole($roleName));
        }

        // Clear the cache again so the new assignments take effect.
        $registrar->forgetCachedPermissions();
    }
}
