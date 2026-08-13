<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * BR-16's role -> permission matrix — see
     * /docs/adr/0011-roles-and-permissions.md for the full derivation.
     *
     * Idempotent: findOrCreate + syncPermissions, so re-running this after a
     * permission is added to (or removed from) a role's list here corrects
     * the role's actual permissions rather than only ever adding new ones.
     *
     * The owner role intentionally gets NO permissions assigned here — it
     * bypasses every check via Gate::before in AppServiceProvider instead.
     */
    private const ROLE_PERMISSIONS = [
        UserRole::Dispatcher->value => [
            PermissionEnum::ManageLeads,
            PermissionEnum::ManageJobs,
            PermissionEnum::ManageCustomers,
            PermissionEnum::ViewIndividualQueue,
        ],
        // "Account managers additionally manage company accounts" (BR-16) —
        // additionally, on top of the dispatcher's base set, not instead of it.
        UserRole::AccountManager->value => [
            PermissionEnum::ManageLeads,
            PermissionEnum::ManageJobs,
            PermissionEnum::ManageCustomers,
            PermissionEnum::ViewIndividualQueue,
            PermissionEnum::ViewCompanyQueue,
        ],
    ];

    public function run(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        // DatabaseSeeder uses WithoutModelEvents, which suppresses the
        // model events spatie's permission cache relies on to invalidate
        // itself — without this, syncPermissions() below fails with
        // "no permission named X" even though it was just created, because
        // it's reading a stale (empty) cache. Documented spatie gotcha.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate(UserRole::Owner->value);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions(array_map(fn (PermissionEnum $p) => $p->value, $permissions));
        }
    }
}
