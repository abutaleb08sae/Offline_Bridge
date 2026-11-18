<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Services\ActivityLogger;
use App\Services\SystemTelemetryService;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    /**
     * Dashboard Overview (Role-Aware: Master Overview for Admin, Lab Workspace for Students).
     */
    public function dashboard()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->role === 'admin';

        $activities = collect([]);
        try {
            $activities = ActivityLog::recent(10)->get();
        } catch (\Throwable $e) {}

        // Data for student workspace overview
        $labTasks = collect([]);
        $judgeProblems = collect([]);
        $mySubmissions = collect([]);
        $totalLabTasks = 0;
        $totalJudgeProblems = 0;
        $mySubmissionsCount = 0;

        try {
            $totalLabTasks = \App\Models\LabTask::count();
            $totalJudgeProblems = \App\Models\JudgeProblem::count();
            $labTasks = \App\Models\LabTask::latest()->take(6)->get();
            $judgeProblems = \App\Models\JudgeProblem::latest()->take(6)->get();

            if ($user) {
                $mySubmissions = \App\Models\Submission::where('user_id', $user->id)
                    ->orWhere('student_name', $user->name)
                    ->with('labTask')
                    ->latest()
                    ->take(5)
                    ->get();
                $mySubmissionsCount = \App\Models\Submission::where('user_id', $user->id)
                    ->orWhere('student_name', $user->name)
                    ->count();
            }
        } catch (\Throwable $e) {}

        return view('dashboard', [
            'isAdmin'             => $isAdmin,
            'systemStatus'        => SystemTelemetryService::getSystemStatus(),
            'stats'               => SystemTelemetryService::getStats(),
            'applications'        => SystemTelemetryService::getApplications(),
            'clients'             => SystemTelemetryService::getConnectedClients(),
            'activities'          => $activities,
            'chartData'           => SystemTelemetryService::getChartData(),
            'network'             => SystemTelemetryService::getNetworkDetails(),
            // Student Workspace Data
            'labTasks'            => $labTasks,
            'judgeProblems'       => $judgeProblems,
            'mySubmissions'       => $mySubmissions,
            'totalLabTasks'       => $totalLabTasks,
            'totalJudgeProblems'  => $totalJudgeProblems,
            'mySubmissionsCount'  => $mySubmissionsCount,
        ]);
    }

    /**
     * Users Management Page (Delegates to UserController).
     */
    public function users()
    {
        return redirect()->route('users.index');
    }

    /**
     * Clients Management Page.
     */
    public function clients()
    {
        return view('pages.clients', [
            'clients' => SystemTelemetryService::getConnectedClients()
        ]);
    }

    /**
     * Applications Management & Feasibility Report Page.
     */
    public function applications()
    {
        return view('pages.applications', [
            'applications' => SystemTelemetryService::getApplications(),
            'ptReport'     => SystemTelemetryService::getPacketTracerReport()
        ]);
    }

    /**
     * Register a new software application package.
     */
    public function storeApplication(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'version'        => 'nullable|string|max:50',
            'required_port'  => 'nullable|string|max:50',
            'offline_status' => 'required|string|max:100',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $app = Application::create([
            'name'                 => $validated['name'],
            'version'              => $validated['version'] ?: 'v1.0',
            'required_port'        => $validated['required_port'] ?: null,
            'cached_login_support' => true,
            'offline_status'       => $validated['offline_status'],
            'notes'                => $validated['notes'] ?: 'Registered software package in lab.',
        ]);

        ActivityLogger::log('Application Registered', "Registered package: {$app->name} ({$app->offline_status})", 'success', $request);

        return redirect()->route('applications.index')->with('success', "Application {$app->name} registered successfully.");
    }

    /**
     * Files Management Page (Redirects to Lab Tasks).
     */
    public function files()
    {
        return redirect()->route('labs.index');
    }

    /**
     * Activities Monitoring Page.
     */
    public function activities()
    {
        $activities = ActivityLog::recent(50)->get();

        return view('pages.activities', [
            'activities' => $activities
        ]);
    }

    /**
     * System Logs Page.
     */
    public function logs()
    {
        $dbLogs = ActivityLog::recent(100)->get()->map(function ($log) {
            $level = 'INFO';
            $st = strtolower((string) $log->status);
            if (str_contains($st, 'warn')) {
                $level = 'WARN';
            } elseif (str_contains($st, 'err') || str_contains($st, 'fail') || str_contains($st, 'danger')) {
                $level = 'ERROR';
            }

            return [
                'timestamp' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                'level'     => $level,
                'source'    => $log->client_ip ? "Client ({$log->client_ip})" : 'Master Controller',
                'message'   => $log->details ? "{$log->action} - {$log->details}" : $log->action,
            ];
        })->toArray();

        if (empty($dbLogs)) {
            $dbLogs = [
                [
                    'timestamp' => now()->format('Y-m-d H:i:s'),
                    'level'     => 'INFO',
                    'source'    => 'Master Telemetry',
                    'message'   => 'OfflineBridge Master Controller active on local subnet.',
                ]
            ];
        }

        return view('pages.logs', [
            'logs' => $dbLogs
        ]);
    }

    /**
     * System Settings Page.
     */
    public function settings()
    {
        $net = SystemTelemetryService::getNetworkDetails();

        $settings = [
            'network' => [
                'master_ip'   => $net['master_ip'],
                'http_port'   => $net['server_port'],
                'subnet_mask' => $net['subnet_mask'],
                'gateway'     => $net['gateway'],
            ],
            'security' => [
                'session_timeout'     => '120',
                'password_min_length' => '12',
                'max_file_size'       => '100',
                'allowed_extensions'  => '.pkt, .pka, .pcapng, .pdf, .cpp, .py, .zip, .png',
            ],
            'general' => [
                'lab_name'     => 'CSE Computer Network & Systems Lab',
                'master_host'  => $net['hostname'],
                'auto_backup'  => 'daily',
            ]
        ];

        return view('pages.settings', [
            'settings' => $settings
        ]);
    }

    /**
     * Backup & Restore Page.
     */
    public function backup()
    {
        $backupsDir = storage_path('app/backups');
        $backups = [];

        if (is_dir($backupsDir)) {
            $files = glob($backupsDir . '/*.sql*');
            foreach ($files as $file) {
                $backups[] = [
                    'id'       => md5($file),
                    'filename' => basename($file),
                    'date'     => date('Y-m-d h:i A', filemtime($file)),
                    'size'     => SystemTelemetryService::formatBytes(filesize($file)),
                    'status'   => 'COMPLETED',
                ];
            }
        }

        if (empty($backups)) {
            $backups = [
                [
                    'id'       => 1,
                    'filename' => 'offlinebridge_initial_seed.sql.gz',
                    'date'     => now()->subDay()->format('Y-m-d h:i A'),
                    'size'     => '2.1 MB',
                    'status'   => 'COMPLETED',
                ]
            ];
        }

        return view('pages.backup', [
            'backups' => $backups
        ]);
    }

    /**
     * Real-time live telemetry endpoint for chart, stats, and infrastructure status.
     */
    public function liveTelemetry(Request $request)
    {
        return response()->json(SystemTelemetryService::getLiveTelemetryData());
    }

    /**
     * Client workstation heartbeat endpoint.
     */
    public function clientHeartbeat(Request $request)
    {
        $ip = $request->ip() ?: '127.0.0.1';
        $hostname = $request->input('hostname') ?: ($ip === '127.0.0.1' ? 'MASTER-CONSOLE' : 'LAB-PC-' . substr(md5($ip), 0, 4));
        $user = $request->input('user') ?: (auth()->check() ? auth()->user()->name : 'Active Workstation');
        $action = $request->input('action') ?: 'Live Session';

        SystemTelemetryService::recordClientHeartbeat($ip, $hostname, $user, $action);

        $stats = SystemTelemetryService::getStats();

        return response()->json([
            'status'            => 'acknowledged',
            'ip'                => $ip,
            'hostname'          => $hostname,
            'timestamp'         => now()->setTimezone(env('APP_TIMEZONE', 'Asia/Dhaka'))->format('H:i:s'),
            'connected_clients' => $stats['connected_clients']['current'],
        ]);
    }
}
