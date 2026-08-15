<?php

/**
 * BR-16: the role -> permission matrix. See
 * /docs/adr/0011-roles-and-permissions.md for the full derivation and the
 * table this test proves.
 *
 * Enforcement lives in route middleware (`permission:<name>`, registered in
 * routes/api.php) plus a Gate::before bypass for the owner role
 * (AppServiceProvider) — not in a form or the UI, per NFR-6. Every area
 * below is a real HTTP request through that middleware, not a direct
 * Gate::allows()/can() call, so these tests would fail exactly the same way
 * a client hitting the API directly would.
 */

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Laravel\Sanctum\Sanctum;

// Seeds via the real seeder, not a duplicated in-test definition — this
// proves PermissionsSeeder itself produces the matrix, not just that the
// matrix would work if someone set it up correctly.
beforeEach(fn () => $this->seed(PermissionsSeeder::class));

/** area => [route, allowed roles] */
function permissionAreas(): array
{
    return [
        'leads' => ['/api/leads', [UserRole::Dispatcher, UserRole::AccountManager]],
        'jobs' => ['/api/jobs', [UserRole::Dispatcher, UserRole::AccountManager]],
        'customers' => ['/api/customers', [UserRole::Dispatcher, UserRole::AccountManager]],
        'individual queue' => ['/api/queues/individual', [UserRole::Dispatcher, UserRole::AccountManager]],
        'company queue' => ['/api/queues/company', [UserRole::AccountManager]],
        'price book' => ['/api/price-book', []],
        'driver rates' => ['/api/driver-rates', []],
        'accounts' => ['/api/accounts', []],
        'audit log' => ['/api/audit-log', []],
    ];
}

function actingAsRole(UserRole $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);
    Sanctum::actingAs($user);

    return $user;
}

foreach (UserRole::cases() as $role) {
    test("{$role->value} role matches the BR-16 matrix", function () use ($role) {
        actingAsRole($role);

        foreach (permissionAreas() as $area => [$route, $allowedRoles]) {
            $response = $this->getJson($route);

            $isOwner = $role === UserRole::Owner;
            $isAllowed = $isOwner || in_array($role, $allowedRoles, true);

            if ($isAllowed) {
                expect($response->status())->toBe(200, "expected {$role->value} to be ALLOWED on '{$area}', got {$response->status()}");
            } else {
                expect($response->status())->toBe(403, "expected {$role->value} to be DENIED on '{$area}', got {$response->status()}");
            }
        }
    });
}

test('queue separation: a dispatcher cannot read the company queue', function () {
    actingAsRole(UserRole::Dispatcher);

    $this->getJson('/api/queues/company')->assertForbidden();
});

test('queue separation: an account-manager can read the company queue', function () {
    actingAsRole(UserRole::AccountManager);

    $this->getJson('/api/queues/company')->assertOk();
});

test('queue separation: the owner reads both queues', function () {
    actingAsRole(UserRole::Owner);

    $this->getJson('/api/queues/individual')->assertOk();
    $this->getJson('/api/queues/company')->assertOk();
});

test('the owner-only areas are rejected for both non-owner roles', function () {
    $ownerOnlyRoutes = ['/api/price-book', '/api/driver-rates', '/api/accounts', '/api/audit-log'];

    actingAsRole(UserRole::Dispatcher);
    foreach ($ownerOnlyRoutes as $route) {
        $this->getJson($route)->assertForbidden();
    }

    actingAsRole(UserRole::AccountManager);
    foreach ($ownerOnlyRoutes as $route) {
        $this->getJson($route)->assertForbidden();
    }
});

test('GET /api/user exposes the current role and permissions for frontend nav gating', function () {
    $user = actingAsRole(UserRole::Dispatcher);

    $response = $this->getJson('/api/user');

    $response->assertOk()->assertJson([
        'role' => 'dispatcher',
    ]);
    expect($response->json('permissions'))
        ->toContain('leads.manage', 'jobs.manage', 'customers.manage', 'queue.individual.view')
        ->not->toContain('price-book.manage', 'audit-log.view');
});

test('GET /api/user reports the full permission set for the owner despite no explicit assignment', function () {
    actingAsRole(UserRole::Owner);

    $response = $this->getJson('/api/user');

    $response->assertOk()->assertJson(['role' => 'owner']);
    expect($response->json('permissions'))->toContain('price-book.manage', 'audit-log.view', 'accounts.manage');
});
