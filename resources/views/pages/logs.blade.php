@extends('layouts.app')

@section('title', 'System Logs')

@section('content')
<!-- TODO: Connect Monolog / storage/logs file parser in LogController -->
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Offline System Event Logs</h3>
                <p class="card-subtitle">Detailed technical logs for network sockets, auth attempts, and application processes</p>
            </div>
            <button class="btn btn-secondary" onclick="showToast('Export Logs', 'Log file saved to storage/logs/export_20261007.log', 'success')">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                <span>Export Log File</span>
            </button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="filter-bar">
            <div class="search-input-wrapper">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                <input type="text" id="logSearchInput" class="form-input" placeholder="Search log entries by message or source..." onkeyup="filterLogs()">
            </div>
        </div>

        <!-- Log Level Tabs -->
        <div class="tab-container">
            <button class="tab-btn active" onclick="filterLogLevel('ALL', this)">All Levels (5)</button>
            <button class="tab-btn" onclick="filterLogLevel('INFO', this)">INFO (3)</button>
            <button class="tab-btn" onclick="filterLogLevel('WARN', this)">WARN (1)</button>
            <button class="tab-btn" onclick="filterLogLevel('ERROR', this)">ERROR (1)</button>
        </div>

        <!-- Dense Log Table -->
        <div class="table-container">
            <table class="table table-dense" id="logsTable">
                <thead>
                    <tr>
                        <th style="width: 180px;">Timestamp</th>
                        <th style="width: 90px;">Level</th>
                        <th style="width: 160px;">Source Module</th>
                        <th>Log Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr data-level="{{ $log['level'] }}">
                            <td style="font-family: monospace; color: var(--text-muted);">{{ $log['timestamp'] }}</td>
                            <td>
                                @if($log['level'] === 'INFO')
                                    <span class="badge badge-info">INFO</span>
                                @elseif($log['level'] === 'WARN')
                                    <span class="badge badge-warning">WARN</span>
                                @else
                                    <span class="badge badge-danger">ERROR</span>
                                @endif
                            </td>
                            <td style="font-weight: 600; font-family: monospace;">{{ $log['source'] }}</td>
                            <td style="font-family: monospace; font-size: var(--font-size-xs);">{{ $log['message'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    function filterLogLevel(level, btn) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const rows = document.querySelectorAll('#logsTable tbody tr');
        rows.forEach(r => {
            const rowLevel = r.getAttribute('data-level');
            const query = document.getElementById('logSearchInput').value.toLowerCase();
            const matchesQuery = r.innerText.toLowerCase().includes(query);
            const matchesLevel = (level === 'ALL' || rowLevel === level);

            if (matchesQuery && matchesLevel) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    }

    function filterLogs() {
        const activeTab = document.querySelector('.tab-btn.active');
        const level = activeTab ? activeTab.innerText.split(' ')[0] : 'ALL';
        filterLogLevel(level, activeTab);
    }
</script>
@endsection
