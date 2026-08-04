<?php

namespace App\Observers;

use App\Exceptions\BusinessRuleException;
use App\Models\Job;

class JobObserver
{
    /**
     * BR-6: pickup time must be in the future at creation.
     *
     * This guards Job::create()/save() — normal Eloquent usage — with a
     * clear application-level error. It does NOT guard a raw query builder
     * or SQL insert, since Eloquent events never fire for those; that gap
     * is why this rule also has a DB trigger (see the migration this
     * ticket adds) as the actual unbypassable layer. See
     * /docs/adr/0003-rule-enforcement.md.
     */
    public function creating(Job $job): void
    {
        if ($job->pickup_at !== null && $job->pickup_at->isPast()) {
            throw BusinessRuleException::violated('BR-6', 'Pickup time must be in the future.');
        }
    }
}
