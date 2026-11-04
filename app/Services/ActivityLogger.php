<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Create an activity audit log entry safely without breaking caller on failure.
     */
    public static function log(string $action, ?string $details = null, string $status = 'info', ?Request $request = null): ActivityLog
    {
        $req = $request ?? request();

        try {
            return ActivityLog::create([
                'client_ip'  => $req->ip() ?: '127.0.0.1',
                'action'     => $action,
                'details'    => $details,
                'status'     => $status,
                'user_agent' => substr((string) ($req->userAgent() ?? ''), 0, 255),
            ]);
        } catch (\Throwable $e) {
            Log::warning("ActivityLogger failed to record log: " . $e->getMessage());
            return new ActivityLog([
                'client_ip'  => $req->ip() ?: '127.0.0.1',
                'action'     => $action,
                'details'    => $details,
                'status'     => $status,
            ]);
        }
    }
}

