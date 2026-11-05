<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

final class CreateSuperAdmin extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'trackyp:super-admin:create';

    /**
     * The command description.
     */
    protected $description = 'Create the first Super Admin for this TrackYP installation';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $role = Role::query()
            ->where('name', Permissions::ROLE_SUPER_ADMIN)
            ->where('guard_name', Permissions::GUARD)
            ->first();

        if (!$role) {
            $this->error('The Super Admin role does not exist.');
            $this->line('Run the permission seeder before creating the administrator.');

            return self::FAILURE;
        }

        $existingAdmin = User::query()
            ->whereHas('roles', function ($query) use ($role) {
                $query->where('roles.id', $role->id);
            })
            ->exists();

        if ($existingAdmin) {
            $this->error('A Super Admin already exists for this installation.');
            $this->line('No additional administrator was created.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('TRACKYP - INITIAL SUPER ADMIN SETUP');
        $this->line('Create the first administrator for this installation.');
        $this->newLine();

        $name = trim((string) $this->ask('Administrator full name'));
        $email = trim((string) $this->ask('Administrator email address'));
        $phone = trim((string) ($this->ask('Phone number (optional)') ?? ''));

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'phone' => $phone === '' ? null : $phone,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['nullable', 'string', 'max:30'],
            ]
        );

        if ($validator->fails()) {
            $this->newLine();
            $this->error('Please correct the following information:');

            foreach ($validator->errors()->all() as $error) {
                $this->line('- ' . $error);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Enter administrator password');
        $passwordConfirmation = (string) $this->secret('Confirm administrator password');

        if (!hash_equals($password, $passwordConfirmation)) {
            $this->error('The passwords do not match.');

            return self::FAILURE;
        }

        $passwordValidator = Validator::make(
            ['password' => $password],
            [
                'password' => [
                    'required',
                    Password::min(12)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols(),
                ],
            ]
        );

        if ($passwordValidator->fails()) {
            $this->newLine();
            $this->error('The password does not meet the security requirements.');

            foreach ($passwordValidator->errors()->all() as $error) {
                $this->line('- ' . $error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use (
            $role,
            $name,
            $email,
            $phone,
            $password
        ): ?User {
            // Lock the role row to prevent simultaneous initial setup commands.
            $lockedRole = Role::query()
                ->whereKey($role->id)
                ->lockForUpdate()
                ->firstOrFail();

            $adminAlreadyExists = User::query()
                ->whereHas('roles', function ($query) use ($lockedRole) {
                    $query->where('roles.id', $lockedRole->id);
                })
                ->exists();

            if ($adminAlreadyExists) {
                return null;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone === '' ? null : $phone,
                'password' => $password,
            ]);

            $user->assignRole($lockedRole);

            return $user;
        });

        if (!$user) {
            $this->error('A Super Admin was created by another process.');
            $this->line('No additional administrator was created.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Super Admin account created successfully.');
        $this->line('Name: ' . $user->name);
        $this->line('Email: ' . $user->email);
        $this->line('Role: Super Admin');
        $this->newLine();

        $this->comment('Keep the administrator login details secure.');

        return self::SUCCESS;
    }
}
