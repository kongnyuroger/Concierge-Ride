<?php

use App\Enums\Permission;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PriceBookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes — anything not listed here must go inside the protected
// group below, not be added here by mistake.
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1');

// Protected by default: every route in this group requires a valid Sanctum
// token. Add new authenticated routes inside this group — permission checks
// (CR-15) will layer on top of this same group later.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user', fn (Request $request) => response()->json($request->user()->toAuthPayload()));

    // BR-16 permission matrix, made real and testable ahead of the features
    // themselves — see /docs/adr/0011-roles-and-permissions.md. Each area
    // below is a thin stub gated by its permission; the ticket that builds
    // the real feature (noted per line) replaces the closure with a real
    // controller — the route path, name, and permission middleware don't
    // need to change.
    $stubAreas = [
        'leads' => Permission::ManageLeads,               // CR-11
        'jobs' => Permission::ManageJobs,                  // CR-12
        'customers' => Permission::ManageCustomers,         // CR-11
        'queues/individual' => Permission::ViewIndividualQueue, // CR-11/12
        'queues/company' => Permission::ViewCompanyQueue,   // CR-20
        'driver-rates' => Permission::ManageDriverRates,    // not yet scoped to a ticket
        'accounts' => Permission::ManageAccounts,           // CR-20
        'audit-log' => Permission::ViewAuditLog,            // CR-32
    ];

    foreach ($stubAreas as $path => $permission) {
        Route::get("/{$path}", fn () => response()->json([
            'area' => $path,
            'status' => 'not yet implemented',
        ]))->middleware("permission:{$permission->value}");
    }

    // CR-13: price-book is real now — same permission gate the stub above
    // used, just a real controller instead of a placeholder closure.
    Route::middleware('permission:'.Permission::ManagePriceBook->value)->group(function () {
        Route::get('/price-book', [PriceBookController::class, 'index']);
        Route::put('/price-book/{priceBookEntry}', [PriceBookController::class, 'update']);
    });
});
