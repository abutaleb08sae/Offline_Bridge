<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MockDataService;
use App\Models\ActivityLog;

class NavigationController extends Controller
{
    /**
     * Master Dashboard Overview.
     */
    public function dashboard()
    {
        $activities = ActivityLog::recent(10)->get();
        if ($activities->isEmpty()) {
            $activities = collect(MockDataService::getActivities());
        }

        return view('dashboard', [
            'systemStatus' => MockDataService::getSystemStatus(),
            'stats' => MockDataService::getStats(),
            'applications' => MockDataService::getApplications(),
            'clients' => MockDataService::getConnectedClients(),
            'activities' => $activities,
            'chartData' => MockDataService::getChartData(),
            'network' => MockDataService::getNetworkDetails(),
        ]);
    }

    /**
     * Users Management Page.
     */
    public function users()
    {
        return view('pages.users', [
            'users' => MockDataService::getUsersList()
        ]);
    }

    /**
     * Clients Management Page.
     */
    public function clients()
    {
        return view('pages.clients', [
            'clients' => MockDataService::getConnectedClients()
        ]);
    }

    /**
     * Applications Management & Feasibility Report Page.
     */
    public function applications()
    {
        return view('pages.applications', [
            'applications' => MockDataService::getApplications(),
            'ptReport' => MockDataService::getPacketTracerReport()
        ]);
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
        if ($activities->isEmpty()) {
            $activities = collect(MockDataService::getActivities());
        }

        return view('pages.activities', [
            'activities' => $activities
        ]);
    }

    /**
     * System Logs Page.
     */
    public function logs()
    {
        return view('pages.logs', [
            'logs' => MockDataService::getLogsList()
        ]);
    }

    /**
     * System Settings Page.
     */
    public function settings()
    {
        return view('pages.settings', [
            'settings' => MockDataService::getSettingsData()
        ]);
    }

    /**
     * Backup & Restore Page.
     */
    public function backup()
    {
        return view('pages.backup', [
            'backups' => MockDataService::getBackupsList()
        ]);
    }
}
