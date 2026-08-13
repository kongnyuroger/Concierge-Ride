<?php

namespace App\Enums;

/**
 * BR-16's three roles. One role per user (spatie/laravel-permission supports
 * multiple, but this app's model — and OwnerSeeder/the tests — assume exactly one).
 */
enum UserRole: string
{
    case Owner = 'owner';
    case Dispatcher = 'dispatcher';
    case AccountManager = 'account-manager';
}
