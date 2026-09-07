<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class JobObserver
{
    public function created(Job $job): void
    {
        $this->recordAudit($job, 'created', null, $job->getAttributes());
    }

    public function updating(Job $job): void
    {
        // Get modified attributes only
        $dirty = $job->getDirty();
        
        if (empty($dirty)) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        foreach ($dirty as $field => $newValue) {
            // Ignore internal system timestamps if irrelevant
            if (in_array($field, ['updated_at'])) continue;

            $oldValues[$field] = $job->getOriginal($field);
            $newValues[$field] = $newValue;
        }

        if (!empty($newValues)) {
            $this->recordAudit($job, 'updated', $oldValues, $newValues);
        }
    }

    private function recordAudit(Job $job, string $action, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'auditable_type' => Job::class,
            'auditable_id' => $job->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);
    }
}