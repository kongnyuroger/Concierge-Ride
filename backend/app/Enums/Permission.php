<?php

namespace App\Enums;

/**
 * BR-16's permission matrix — see /docs/adr/0011-roles-and-permissions.md.
 * The string values are what's actually stored in spatie/laravel-permission's
 * `permissions` table; this enum exists so every reference to a permission
 * name (seeder, routes, tests) goes through one typo-proof source.
 */
enum Permission: string
{
    case ManageLeads = 'leads.manage';
    case ManageJobs = 'jobs.manage';
    case ManageCustomers = 'customers.manage';
    case ViewIndividualQueue = 'queue.individual.view';
    case ViewCompanyQueue = 'queue.company.view';
    case ManagePriceBook = 'price-book.manage';
    case ManageDriverRates = 'driver-rates.manage';
    case ManageAccounts = 'accounts.manage';
    case ViewAuditLog = 'audit-log.view';
}
