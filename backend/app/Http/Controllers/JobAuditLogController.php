<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class JobAuditLogController extends Controller
{
    use AuthorizesRequests;

    public function index(Job $job): JsonResponse
    {
        $this->authorize('viewAuditLogs', $job);

        $logs = $job->auditLogs()
            ->with('user:id,name,email,role')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $logs
        ]);
    }
}