<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * Create an activity audit log entry.
     */
    public static function log(string $action, ?string $details = null, string $status = 'info', ?Request $request = null): ActivityLog
    {
        $req = $request ?? request();

        return ActivityLog::create([
            'client_ip'  => $req->ip() ?? '127.0.0.1',
            'action'     => $action,
            'details'    => $details,
            'status'     => $status,
            'user_agent' => substr((string) $req->userAgent(), 0, 255),
        ]);
    }
}

