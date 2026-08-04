<?php

use App\Http\Controllers\Auth\AuthController;
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

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
