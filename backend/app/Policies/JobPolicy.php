<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    /**
     * View audit logs for a specific job (BR-16: Owner Only).
     */
    public function viewAuditLogs(User $user, Job $job): bool
    {
        return $user->hasRole(UserRole::Owner->value);
    }
}