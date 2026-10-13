@extends('layouts.app')

@section('title', 'Backup & Restore')

@section('content')
<!-- TODO: Connect mysqldump / sqlite backup process execution script in BackupController -->
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Offline Database & Storage Backups</h3>
                <p class="card-subtitle">Generate local database archives and restore system snapshots</p>
            </div>
            <button class="btn btn-primary" onclick="showToast('Backup Initiated', 'Database export dump started in background...', 'info')">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                <span>Create Backup Now</span>
            </button>
        </div>

        <!-- Backups List Table -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Backup Archive File</th>
                        <th>Creation Date & Time</th>
                        <th>File Size</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backups as $b)
                        <tr>
                            <td style="font-weight: 600; font-family: monospace;">{{ $b['filename'] }}</td>
                            <td style="font-size: var(--font-size-xs); color: var(--text-muted);">{{ $b['date'] }}</td>
                            <td style="font-size: var(--font-size-xs); font-family: monospace;">{{ $b['size'] }}</td>
                            <td>
                                @if($b['status'] === 'COMPLETED')
                                    <span class="badge badge-success">COMPLETED</span>
                                @elseif($b['status'] === 'IN PROGRESS')
                                    <span class="badge badge-warning">IN PROGRESS</span>
                                @else
                                    <span class="badge badge-danger">FAILED</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" onclick="showToast('Restore Initiated', 'Database restore requested for {{ $b['filename'] }}', 'warning')">
                                    Restore
                                </button>
                                <button class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" onclick="showToast('Backup Deleted', 'Removed archive file', 'info')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
