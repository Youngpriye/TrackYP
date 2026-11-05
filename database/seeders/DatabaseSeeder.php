<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Only roles and permissions are seeded. No users, administrator
     * accounts, passwords or sample data are created here. The first
     * Super Admin is created separately through a secure Artisan command.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
    }
}