<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class OwnerSeeder extends Seeder
{
    /**
     * Seed the owner account from OWNER_EMAIL / OWNER_PASSWORD env vars.
     * Idempotent — re-running updates the existing owner rather than duplicating it.
     * Depends on PermissionsSeeder having already created the owner role.
     */
    public function run(): void
    {
        $email = env('OWNER_EMAIL');
        $password = env('OWNER_PASSWORD');

        if (! $email || ! $password) {
            throw new RuntimeException(
                'OWNER_EMAIL and OWNER_PASSWORD must be set in .env before running OwnerSeeder.'
            );
        }

        $owner = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('OWNER_NAME', 'Owner'),
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        $owner->syncRoles([UserRole::Owner->value]);
    }
}
