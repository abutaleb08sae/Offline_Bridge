<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NavigationController;

/*
|--------------------------------------------------------------------------
| Web Routes - OfflineBridge Master Control Panel
|--------------------------------------------------------------------------
*/

Route::get('/', [NavigationController::class, 'dashboard'])->name('dashboard');
Route::get('/users', [NavigationController::class, 'users'])->name('users.index');
Route::get('/clients', [NavigationController::class, 'clients'])->name('clients.index');
Route::get('/applications', [NavigationController::class, 'applications'])->name('applications.index');
Route::get('/files', [NavigationController::class, 'files'])->name('files.index');
Route::get('/activities', [NavigationController::class, 'activities'])->name('activities.index');
Route::get('/logs', [NavigationController::class, 'logs'])->name('logs.index');
Route::get('/settings', [NavigationController::class, 'settings'])->name('settings.index');
Route::get('/backup', [NavigationController::class, 'backup'])->name('backup.index');
