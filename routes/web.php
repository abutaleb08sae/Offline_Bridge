<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NavigationController;
use App\Http\Controllers\LabTaskController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\LocalJudgeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

/*
|--------------------------------------------------------------------------
| Web Routes - OfflineBridge Master Control Panel
|--------------------------------------------------------------------------
*/

Route::get('/', [NavigationController::class, 'dashboard'])->name('dashboard');
Route::get('/applications', [NavigationController::class, 'applications'])->name('applications.index');
Route::get('/files', [LabTaskController::class, 'index'])->name('files.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::match(['put', 'patch'], '/users/{id}', [UserController::class, 'update'])->whereNumber('id')->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->whereNumber('id')->name('users.destroy');
    Route::get('/clients', [NavigationController::class, 'clients'])->name('clients.index');
    Route::get('/activities', [NavigationController::class, 'activities'])->name('activities.index');
    Route::get('/logs', [NavigationController::class, 'logs'])->name('logs.index');
    Route::get('/settings', [NavigationController::class, 'settings'])->name('settings.index');
    Route::get('/backup', [NavigationController::class, 'backup'])->name('backup.index');
});

/*
|--------------------------------------------------------------------------
| Offline Local Programming Judge
|--------------------------------------------------------------------------
*/
Route::prefix('judge')->name('judge.')->group(function () {
    Route::get('/', [LocalJudgeController::class, 'index'])->name('index');
    Route::get('/problem/{id}', [LocalJudgeController::class, 'show'])->whereNumber('id')->name('show');
    Route::post('/submit', [LocalJudgeController::class, 'submit'])->middleware('throttle:10,1')->name('submit');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/problems/create', [LocalJudgeController::class, 'createProblem'])->name('admin.problems.create');
        Route::post('/problems/store', [LocalJudgeController::class, 'storeProblem'])->name('admin.problems.store');
        Route::get('/problems/{id}/edit', [LocalJudgeController::class, 'editProblem'])
            ->whereNumber('id')
            ->name('problems.edit');
        Route::match(['put', 'patch'], '/problems/{id}', [LocalJudgeController::class, 'updateProblem'])
            ->whereNumber('id')
            ->name('problems.update');
        Route::get('/submissions', [LocalJudgeController::class, 'allSubmissions'])->name('admin.submissions');
        Route::get('/submissions/{submission}', [LocalJudgeController::class, 'showSubmission'])
            ->whereNumber('submission')
            ->name('admin.submissions.show');
    });
});

/*
|--------------------------------------------------------------------------
| Lab Tasks & Local File Distribution Routes (.pkt Support)
|--------------------------------------------------------------------------
*/
Route::get('/labs', [LabTaskController::class, 'index'])->name('labs.index');
Route::get('/labs/{id}/download', [LabTaskController::class, 'download'])->name('labs.download');
Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/labs/upload', [LabTaskController::class, 'store'])->name('labs.store');
    Route::delete('/labs/{id}', [LabTaskController::class, 'destroy'])->name('labs.destroy');
});

/*
|--------------------------------------------------------------------------
| Student Submissions & Instructor Evaluation Routes
|--------------------------------------------------------------------------
*/
Route::get('/submit', [SubmissionController::class, 'create'])->name('submissions.create');
Route::get('/labs/submit', [SubmissionController::class, 'create'])->name('labs.submit');
Route::post('/submit', [LabTaskController::class, 'storeSubmission'])->name('submissions.store');
Route::post('/submissions/submit', [LabTaskController::class, 'storeSubmission']);
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/admin/submissions/{id}/download', [SubmissionController::class, 'download'])->name('submissions.download');
    Route::get('/admin/tasks/{id}/download-all', [SubmissionController::class, 'batchDownload'])
        ->name('admin.tasks.downloadAll');
});

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
})->middleware(['auth', 'admin'])->name('activities.stream');
