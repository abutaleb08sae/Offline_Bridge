<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NavigationController;
use App\Http\Controllers\LabTaskController;
use App\Http\Controllers\SubmissionController;

/*
|--------------------------------------------------------------------------
| Web Routes - OfflineBridge Master Control Panel
|--------------------------------------------------------------------------
*/

Route::get('/', [NavigationController::class, 'dashboard'])->name('dashboard');
Route::get('/users', [NavigationController::class, 'users'])->name('users.index');
Route::get('/clients', [NavigationController::class, 'clients'])->name('clients.index');
Route::get('/applications', [NavigationController::class, 'applications'])->name('applications.index');
Route::get('/files', [LabTaskController::class, 'index'])->name('files.index');
Route::get('/activities', [NavigationController::class, 'activities'])->name('activities.index');
Route::get('/logs', [NavigationController::class, 'logs'])->name('logs.index');
Route::get('/settings', [NavigationController::class, 'settings'])->name('settings.index');
Route::get('/backup', [NavigationController::class, 'backup'])->name('backup.index');

/*
|--------------------------------------------------------------------------
| Lab Tasks & Local File Distribution Routes (.pkt Support)
|--------------------------------------------------------------------------
*/
Route::get('/labs', [LabTaskController::class, 'index'])->name('labs.index');
Route::post('/labs/upload', [LabTaskController::class, 'store'])->name('labs.store');
Route::get('/labs/{id}/download', [LabTaskController::class, 'download'])->name('labs.download');
Route::delete('/labs/{id}', [LabTaskController::class, 'destroy'])->name('labs.destroy');

/*
|--------------------------------------------------------------------------
| Student Submissions & Instructor Evaluation Routes
|--------------------------------------------------------------------------
*/
Route::get('/submit', [SubmissionController::class, 'create'])->name('submissions.create');
Route::post('/submit', [SubmissionController::class, 'store'])->name('submissions.store');
Route::post('/submissions/submit', [SubmissionController::class, 'store']);
Route::get('/admin/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
Route::get('/admin/submissions/{id}/download', [SubmissionController::class, 'download'])->name('submissions.download');

/*
|--------------------------------------------------------------------------
| Live Activity Logs API Route (Offline LAN Polling - Zero WebSockets)
|--------------------------------------------------------------------------
*/
Route::get('/api/activities/stream', function (\Illuminate\Http\Request $request) {
    $limit = min((int) $request->query('limit', 15), 50);
    $logs = \App\Models\ActivityLog::recent($limit)->get();

    return response()->json([
        'latest_id' => \App\Models\ActivityLog::max('id') ?? 0,
        'count' => $logs->count(),
        'activities' => $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'client_ip' => $log->client_ip,
                'action' => $log->action,
                'details' => $log->details,
                'status' => $log->status,
                'category' => $log->category,
                'icon' => $log->icon,
                'time' => $log->formatted_time,
                'date' => $log->created_at ? $log->created_at->format('M d, Y h:i A') : '',
            ];
        }),
    ]);
})->name('activities.stream');
