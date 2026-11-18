@extends('layouts.app')

@php
    $isAdmin = isset($isAdmin) ? $isAdmin : (auth()->check() && auth()->user()->role === 'admin');
@endphp

@section('title', $isAdmin ? 'Master Dashboard Overview' : 'Student Lab Workspace')

@section('content')
@if($isAdmin)
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <!-- Section 1: System Status Cards Row -->
    <div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-3);">
            <h2 style="font-size: var(--font-size-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; display: flex; align-items: center; gap: var(--space-2);">
                <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--primary); box-shadow: 0 0 8px var(--primary);"></span>
                System Infrastructure Status
            </h2>
            <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-family: monospace;">Air-Gapped Node #01</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: var(--space-4);">
            @foreach($systemStatus as $item)
                @php($slug = Str::slug($item['title']))
                <div class="card" style="padding: var(--space-5);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-3);">
                        <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
                            {{ $item['title'] }}
                        </span>
                        @if($item['type'] === 'success')
                            <span id="badge-{{ $slug }}" class="badge badge-success">{{ $item['badge'] }}</span>
                        @elseif($item['type'] === 'warning')
                            <span id="badge-{{ $slug }}" class="badge badge-warning">{{ $item['badge'] }}</span>
                        @elseif($item['type'] === 'danger')
                            <span id="badge-{{ $slug }}" class="badge badge-danger">{{ $item['badge'] }}</span>
                        @else
                            <span id="badge-{{ $slug }}" class="badge badge-neutral" style="background-color: rgba(37, 99, 235, 0.15); color: #3b82f6; border-color: rgba(37, 99, 235, 0.3);">{{ $item['badge'] }}</span>
                        @endif
                    </div>
                    <div id="val-{{ $slug }}" style="font-size: var(--font-size-2xl); font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem; letter-spacing: -0.02em;">
                        {{ $item['value'] }}
                    </div>
                    <div id="desc-{{ $slug }}" style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 500;">
                        {{ $item['desc'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Section 2: Metrics Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: var(--space-4);">
        <!-- Connected Clients -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Connected Clients</span>
                    <div id="statConnectedClients" style="font-size: var(--font-size-2xl); font-weight: 800; margin-top: 0.25rem; letter-spacing: -0.02em; color: var(--text-main);">
                        {{ $stats['connected_clients']['current'] }} / {{ $stats['connected_clients']['total'] }}
                    </div>
                </div>
                <div class="glass-icon-box emerald">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 11.25v-6" />
                    </svg>
                </div>
            </div>
            <div style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
                <span id="statConnectedClientsPercentage" style="color: var(--color-success-text); font-weight: 700;">{{ $stats['connected_clients']['percentage'] }}%</span> lab capacity occupied
            </div>
        </div>

        <!-- Registered Users -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Registered Users</span>
                    <div id="statRegisteredUsers" style="font-size: var(--font-size-2xl); font-weight: 800; margin-top: 0.25rem; letter-spacing: -0.02em; color: var(--text-main);">
                        {{ $stats['registered_users']['total'] }}
                    </div>
                </div>
                <div class="glass-icon-box blue">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
            </div>
            <div id="statRegisteredUsersDesc" style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 500;">
                {{ $stats['registered_users']['description'] ?? ($stats['registered_users']['students'] . ' Students, ' . $stats['registered_users']['instructors'] . ' Instructors') }}
            </div>
        </div>

        <!-- Supported Applications -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Supported Apps</span>
                    <div id="statSupportedApps" style="font-size: var(--font-size-2xl); font-weight: 800; margin-top: 0.25rem; letter-spacing: -0.02em; color: var(--text-main);">
                        {{ $stats['supported_apps']['total'] }} Packages
                    </div>
                </div>
                <div class="glass-icon-box cyan">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </div>
            </div>
            <div id="statSupportedAppsDesc" style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 500;">
                {{ $stats['supported_apps']['description'] ?? 'Verified for Offline Lab' }}
            </div>
        </div>

        <!-- Files Stored -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Files Stored</span>
                    <div id="statFilesStored" style="font-size: var(--font-size-2xl); font-weight: 800; margin-top: 0.25rem; letter-spacing: -0.02em; color: var(--text-main);">
                        {{ $stats['files_stored']['count'] }} Files
                    </div>
                </div>
                <div class="glass-icon-box amber">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
            </div>
            <div id="statFilesStoredDesc" style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 500;">
                {{ $stats['files_stored']['description'] ?? ('Total Storage: ' . $stats['files_stored']['size']) }}
            </div>
        </div>
    </div>

    <!-- Section 6 & Section 7 Grid: Chart + Quick Actions -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6);">
        <!-- Section 6: Client Connections Lightweight SVG Chart (Picture 2) -->
        <div class="card" style="position: relative;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 class="card-title">Client Connections Over 12 Hours</h3>
                    <p class="card-subtitle">Real-time workstation activity on the local Master network</p>
                </div>
                <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; padding: 0.35rem 0.85rem; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.2);">
                    <span class="live-dot"></span>
                    LIVE MONITORING
                </span>
            </div>

            <!-- Custom Clean Responsive Inline SVG Chart -->
            <div style="width: 100%; overflow-x: auto; padding-top: var(--space-2); position: relative;">
                <!-- Tooltip floating overlay (Optimized High-Performance) -->
                <div id="chartTooltip" style="position: absolute; display: none; pointer-events: none; background: rgba(7, 35, 36, 0.95); color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; top: 10px; right: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.25); border: 1px solid rgba(255, 255, 255, 0.15);"></div>

                <svg id="chartSvg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 650 200" style="width: 100%; height: auto; display: block; overflow: visible;">
                    <defs>
                        <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#0D5C5E" stop-opacity="0.32"/>
                            <stop offset="100%" stop-color="#0D5C5E" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>

                    <!-- Horizontal Grid Lines -->
                    <line x1="40" y1="20" x2="630" y2="20" stroke="var(--border-color)" stroke-dasharray="3,3" opacity="0.7" />
                    <line x1="40" y1="60" x2="630" y2="60" stroke="var(--border-color)" stroke-dasharray="3,3" opacity="0.7" />
                    <line x1="40" y1="100" x2="630" y2="100" stroke="var(--border-color)" stroke-dasharray="3,3" opacity="0.7" />
                    <line x1="40" y1="140" x2="630" y2="140" stroke="var(--border-color)" stroke-dasharray="3,3" opacity="0.7" />

                    <!-- Y-Axis Labels -->
                    <g id="chartYAxisGroup">
                        <text id="chartY0" x="30" y="24" fill="var(--text-muted)" font-size="11" text-anchor="end">{{ $chartData['yLabels'][0] ?? 5 }}</text>
                        <text id="chartY1" x="30" y="64" fill="var(--text-muted)" font-size="11" text-anchor="end">{{ $chartData['yLabels'][1] ?? 3 }}</text>
                        <text id="chartY2" x="30" y="104" fill="var(--text-muted)" font-size="11" text-anchor="end">{{ $chartData['yLabels'][2] ?? 2 }}</text>
                        <text id="chartY3" x="30" y="144" fill="var(--text-muted)" font-size="11" text-anchor="end">0</text>
                    </g>

                    <!-- Area Fill Path -->
                    <path id="chartAreaPath" d="{{ $chartData['areaPath'] }}" fill="url(#chartGradient)" style="transition: d 0.4s ease;" />

                    <!-- Smooth Trend Line Path: Deep Teal Green -->
                    <path id="chartLinePath" d="{{ $chartData['linePath'] }}" fill="none" stroke="#0D5C5E" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="transition: d 0.4s ease;" />

                    <!-- Dynamic Interactive Data Points (Deep Teal + Coral Orange Peak) -->
                    <g id="chartCirclesGroup">
                        @foreach($chartData['circles'] as $c)
                            @if($c['isPeak'])
                                <circle cx="{{ $c['cx'] }}" cy="{{ $c['cy'] }}" r="5.5" fill="#FF5E3A" stroke="#ffffff" stroke-width="2.5" class="chart-point" data-val="{{ $c['value'] }}" data-lbl="{{ $c['label'] }}" />
                            @else
                                <circle cx="{{ $c['cx'] }}" cy="{{ $c['cy'] }}" r="4" fill="#ffffff" stroke="#0D5C5E" stroke-width="2.5" class="chart-point" data-val="{{ $c['value'] }}" data-lbl="{{ $c['label'] }}" />
                            @endif
                        @endforeach
                    </g>

                    <!-- Peak Value Callout Highlight: Coral Orange -->
                    <g id="chartPeakGroup">
                        <rect id="chartPeakRect" x="{{ $chartData['peakRectX'] }}" y="{{ $chartData['peakRectY'] }}" width="{{ $chartData['peakRectWidth'] ?? 54 }}" height="20" rx="4" fill="#FF5E3A" />
                        <text id="chartPeakText" x="{{ $chartData['peakTextX'] }}" y="{{ $chartData['peakTextY'] }}" fill="#ffffff" font-size="10" font-weight="700" text-anchor="middle">{{ $chartData['peakLabel'] }}</text>
                    </g>

                    <!-- X-Axis Labels -->
                    <g id="chartXAxisGroup">
                        @foreach($chartData['circles'] as $c)
                            <text x="{{ $c['cx'] }}" y="165" fill="var(--text-muted)" font-size="11" text-anchor="middle" {{ $c['isPeak'] ? 'font-weight=600' : '' }}>
                                {{ $c['label'] }}{{ $c['isPeak'] ? ' (Peak)' : '' }}
                            </text>
                        @endforeach
                    </g>
                </svg>
            </div>
        </div>

        <!-- Section 7: Quick Actions Panel -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Quick Actions</h3>
                    <p class="card-subtitle">Master PC Management</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                <!-- Add User -->
                <a href="{{ route('users.index') }}" class="glass-btn">
                    <div class="glass-icon-box blue" style="width: 32px; height: 32px; border-radius: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.105a6.375 6.375 0 0112.75 0v.105" />
                        </svg>
                    </div>
                    <span>Add New Student / User</span>
                </a>

                <!-- Register Application -->
                <a href="{{ route('applications.index') }}" class="glass-btn">
                    <div class="glass-icon-box cyan" style="width: 32px; height: 32px; border-radius: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span>Register Application Package</span>
                </a>

                <!-- Upload File -->
                <a href="{{ route('labs.index') }}" class="glass-btn">
                    <div class="glass-icon-box emerald" style="width: 32px; height: 32px; border-radius: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>
                    <span>Upload Lab Material / File</span>
                </a>

                <!-- Create Backup -->
                <a href="{{ route('backup.index') }}" class="glass-btn">
                    <div class="glass-icon-box amber" style="width: 32px; height: 32px; border-radius: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                        </svg>
                    </div>
                    <span>Create Offline DB Backup</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Section 3: Applications Panel Cards -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Managed Software Applications</h3>
                <p class="card-subtitle">Local offline applications deployed across client PCs</p>
            </div>
            <a href="{{ route('applications.index') }}" class="glass-btn" style="padding: 0.35rem 0.75rem; font-size: var(--font-size-xs);">View All Apps</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-4);">
            @foreach($applications as $app)
                <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-2);">
                            <div>
                                <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main);">{{ $app['name'] }}</h4>
                                <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-family: monospace;">{{ $app['version'] }}</span>
                            </div>

                            <!-- Offline Status Badges -->
                            @if($app['status'] === 'Supported')
                                <span class="badge badge-supported">SUPPORTED</span>
                            @elseif($app['status'] === 'Partial')
                                <span class="badge badge-partial">PARTIALLY SUPPORTED</span>
                            @elseif($app['status'] === 'Testing')
                                <span class="badge badge-testing">TESTING</span>
                            @elseif($app['status'] === 'Unsupported')
                                <span class="badge badge-unsupported">UNSUPPORTED</span>
                            @else
                                <span class="badge badge-planned">PLANNED</span>
                            @endif
                        </div>

                        <div style="font-size: var(--font-size-xs); color: var(--primary); font-weight: 600; margin-bottom: var(--space-2);">
                            Category: {{ $app['category'] }}
                        </div>

                        <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin-bottom: var(--space-4); line-height: 1.4;">
                            {{ $app['note'] }}
                        </p>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: var(--space-3); margin-top: var(--space-2);">
                        <span style="font-size: var(--font-size-xs); color: var(--text-muted);">
                            Active Installs: <strong style="color: var(--text-main);">{{ $app['installed_clients'] }} PCs</strong>
                        </span>
                        <a href="{{ route('applications.index') }}" class="glass-btn" style="padding: 0.25rem 0.65rem; font-size: var(--font-size-xs);">Configure</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Section 4 & Section 5 & Section 8 Grid: Clients Table + Activity Feed + Network Info -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6);">

        <!-- Left Column: Connected Clients Table -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Connected Workstations (LAN Clients)</h3>
                    <p class="card-subtitle">Active client PCs communicating with Master Host</p>
                </div>
                <a href="{{ route('clients.index') }}" class="glass-btn" style="padding: 0.35rem 0.75rem; font-size: var(--font-size-xs);">Manage Clients</a>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client Hostname</th>
                            <th>IP Address</th>
                            <th>Assigned Student</th>
                            <th>Active App</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                            <tr>
                                <td style="font-weight: 600; font-family: monospace;">{{ $client['hostname'] }}</td>
                                <td><code style="background: var(--bg-surface-hover); border: 1px solid var(--glass-border); padding: 0.2rem 0.45rem; border-radius: 4px; font-size: 0.8rem;">{{ $client['ip'] }}</code></td>
                                <td style="font-size: var(--font-size-xs);">{{ $client['user'] }}</td>
                                <td style="font-size: var(--font-size-xs);">{{ $client['app'] }}</td>
                                <td>
                                    @if($client['status'] === 'online')
                                        <span class="badge badge-success">ONLINE</span>
                                    @elseif($client['status'] === 'idle')
                                        <span class="badge badge-warning">IDLE</span>
                                    @else
                                        <span class="badge badge-neutral">OFFLINE</span>
                                    @endif
                                </td>
                                <td style="font-size: var(--font-size-xs); color: var(--text-muted);">{{ $client['last_seen'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column: Recent Activity Feed + Network Info Card -->
        <div style="display: flex; flex-direction: column; gap: var(--space-6);">

            <!-- Section 5: Recent Activity Feed -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Recent Activity Feed</h3>
                        <p class="card-subtitle">Live system events stream</p>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.65rem; display: inline-flex; align-items: center; gap: 5px;">
                        <span style="display: inline-block; width: 6px; height: 6px; background-color: #10b981; border-radius: 50%; box-shadow: 0 0 6px #10b981;"></span>
                        Live LAN
                    </span>
                </div>

                <div class="activity-list" id="dashboardActivityList">
                    @foreach($activities as $act)
                        <div class="activity-item">
                            <div class="activity-icon {{ $act['icon'] }}">
                                @if($act['icon'] === 'user')
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>
                                @elseif($act['icon'] === 'app')
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                                @elseif($act['icon'] === 'file')
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" /></svg>
                                @elseif($act['icon'] === 'backup')
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                @endif
                            </div>
                            <div class="activity-details">
                                <span class="activity-message">{{ $act['message'] }}</span>
                                <div style="display: flex; align-items: center; gap: var(--space-2); margin-top: 2px;">
                                    @if(!empty($act['client_ip']))
                                        <span class="badge badge-neutral" style="font-family: monospace; font-size: 0.65rem;">{{ $act['client_ip'] }}</span>
                                    @endif
                                    <span class="activity-time">{{ $act['time'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 8: Network Info Card -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Master Network Details</h3>
                        <p class="card-subtitle">Server Host Configuration</p>
                    </div>
                    <span class="badge badge-success">Active Master</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: var(--space-3); font-size: var(--font-size-xs);">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Master IP:</span>
                        <strong style="font-family: monospace; color: var(--primary);">{{ $network['master_ip'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Hostname:</span>
                        <strong style="font-family: monospace; color: var(--text-main);">{{ $network['hostname'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Subnet Mask:</span>
                        <strong style="font-family: monospace; color: var(--text-main);">{{ $network['subnet_mask'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Server Port:</span>
                        <strong style="font-family: monospace; color: var(--text-main);">{{ $network['server_port'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Default Gateway:</span>
                        <strong style="font-family: monospace; color: var(--text-main);">{{ $network['gateway'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">MAC Address:</span>
                        <strong style="font-family: monospace; color: var(--text-main);">{{ $network['mac_address'] }}</strong>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@else
{{-- STUDENT LAB WORKSPACE: HOW TO GET A FILE, SOLVE A PROBLEM, AND SUBMIT IT --}}
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <!-- Student Welcome & Workstation Banner -->
    <div class="card" style="padding: var(--space-6); background: linear-gradient(135deg, rgba(255, 253, 248, 0.98), rgba(247, 241, 230, 0.92)); border-color: rgba(13, 92, 94, 0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-4);">
            <div style="display: flex; align-items: center; gap: var(--space-4);">
                <div class="glass-icon-box teal" style="width: 52px; height: 52px; border-radius: 16px; font-weight: 800; font-size: 1.25rem;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'ST', 0, 2)) }}
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: 2px;">
                        <h2 style="font-size: var(--font-size-xl); font-weight: 800; color: var(--text-main); margin: 0; line-height: 1.2;">
                            Welcome, {{ auth()->user()->name ?? 'Student' }}
                        </h2>
                        <span class="badge badge-coral">STUDENT WORKSTATION</span>
                    </div>
                    <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin: 0;">
                        Offline Computer Lab Workstation • Connected to Master Lab Controller
                    </p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
                <div class="network-status-badge">
                    <span class="status-dot lan-online"></span>
                    <span>LAN: ONLINE</span>
                    <span class="status-sep">|</span>
                    <span style="font-family: monospace; color: var(--text-muted);">{{ request()->ip() }}</span>
                </div>
                <span class="badge badge-success">AIR-GAPPED LAB</span>
            </div>
        </div>
    </div>

    <!-- Section: 3-Step Workflow Overview (Get File -> Solve Problem -> Submit) -->
    <div>
        <div style="margin-bottom: var(--space-3);">
            <h3 style="font-size: var(--font-size-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; display: flex; align-items: center; gap: var(--space-2);">
                <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--color-accent); box-shadow: 0 0 8px var(--color-accent);"></span>
                How to Complete Your Lab Work (3 Easy Steps)
            </h3>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-4);">
            <!-- Step 1: Download Lab Materials -->
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 3px solid #0D5C5E;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                        <span class="badge badge-coral" style="font-size: 0.65rem;">STEP 1</span>
                        <div class="glass-icon-box teal" style="width: 36px; height: 36px; border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </div>
                    </div>

                    <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main); margin-bottom: var(--space-2);">
                        1. Get Lab Files & Tasks
                    </h4>
                    <p style="font-size: var(--font-size-xs); color: var(--text-muted); line-height: 1.5; margin-bottom: var(--space-4);">
                        Download assignment instructions, question sheets, starter source code, and Cisco Packet Tracer (<code>.pkt</code>) network lab files provided by your instructor.
                    </p>
                </div>

                <div>
                    <div style="font-size: var(--font-size-xs); color: var(--text-muted); margin-bottom: var(--space-3); font-weight: 600;">
                        <span style="color: var(--primary); font-weight: 700;">{{ $totalLabTasks ?? 0 }}</span> lab files available for download
                    </div>
                    <a href="{{ route('labs.index') }}" class="btn btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: var(--space-2);">
                        <span>Browse & Download Files</span>
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Step 2: Solve on Local Judge -->
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 3px solid #0D5C5E;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                        <span class="badge badge-coral" style="font-size: 0.65rem;">STEP 2</span>
                        <div class="glass-icon-box teal" style="width: 36px; height: 36px; border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8 8-4 4 4 4m8-8 4 4-4 4m-3-7-2 6" />
                            </svg>
                        </div>
                    </div>

                    <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main); margin-bottom: var(--space-2);">
                        2. Solve on Offline Judge
                    </h4>
                    <p style="font-size: var(--font-size-xs); color: var(--text-muted); line-height: 1.5; margin-bottom: var(--space-4);">
                        Practice competitive programming problems offline. Read problem statements, code in C++, C, Python, or Java, and get automated offline compilation verdicts.
                    </p>
                </div>

                <div>
                    <div style="font-size: var(--font-size-xs); color: var(--text-muted); margin-bottom: var(--space-3); font-weight: 600;">
                        <span style="color: var(--primary); font-weight: 700;">{{ $totalJudgeProblems ?? 0 }}</span> offline judge problems active
                    </div>
                    <a href="{{ route('judge.index') }}" class="btn btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: var(--space-2);">
                        <span>Open Local Judge</span>
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Step 3: Submit Solution -->
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 3px solid #FF5E3A;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-3);">
                        <span class="badge badge-coral" style="font-size: 0.65rem;">STEP 3</span>
                        <div class="glass-icon-box coral" style="width: 36px; height: 36px; border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                        </div>
                    </div>

                    <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main); margin-bottom: var(--space-2);">
                        3. Submit Completed Task
                    </h4>
                    <p style="font-size: var(--font-size-xs); color: var(--text-muted); line-height: 1.5; margin-bottom: var(--space-4);">
                        Submit your code, report, or Packet Tracer (<code>.pkt</code>) file. The local Master controller immediately logs an immutable SHA-256 submission receipt.
                    </p>
                </div>

                <div>
                    <div style="font-size: var(--font-size-xs); color: var(--text-muted); margin-bottom: var(--space-3); font-weight: 600;">
                        <span style="color: var(--color-accent); font-weight: 700;">{{ $mySubmissionsCount ?? 0 }}</span> submissions uploaded by you
                    </div>
                    <a href="{{ route('labs.submit') }}" class="btn btn-accent" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: var(--space-2);">
                        <span>Submit Solution Now</span>
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Available Lab Tasks & My Recent Submissions Grid -->
    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: var(--space-6);">
        <!-- Left: Available Lab Materials & Active Problems -->
        <div style="display: flex; flex-direction: column; gap: var(--space-5);">
            <!-- Available Lab Materials Card -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Available Lab Materials</h3>
                        <p class="card-subtitle">Download tasks & network topologies distributed by instructor</p>
                    </div>
                    <a href="{{ route('labs.index') }}" style="font-size: var(--font-size-xs); color: var(--primary); font-weight: 700;">View All &rarr;</a>
                </div>

                @if(isset($labTasks) && $labTasks->count() > 0)
                    <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                        @foreach($labTasks as $task)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: var(--space-3); background: var(--bg-surface-hover); border: 1px solid var(--glass-border); border-radius: 12px; gap: var(--space-3);">
                                <div style="display: flex; align-items: center; gap: var(--space-3); min-width: 0;">
                                    <div class="glass-icon-box teal" style="width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <div style="min-width: 0;">
                                        <div style="font-weight: 700; font-size: var(--font-size-sm); color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $task->title }}
                                        </div>
                                        <div style="font-size: var(--font-size-xs); color: var(--text-muted); font-family: monospace;">
                                            {{ $task->file_name }} • {{ $task->formatted_file_size }}
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('labs.download', $task->id) }}" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: var(--font-size-xs); flex-shrink: 0;">
                                    Download
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state-card">
                        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin: 0;">No lab materials have been published by instructors yet.</p>
                    </div>
                @endif
            </div>

            <!-- Active Judge Problems Card -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Practice on Local Judge</h3>
                        <p class="card-subtitle">Solve problems offline with automated testcase evaluation</p>
                    </div>
                    <a href="{{ route('judge.index') }}" style="font-size: var(--font-size-xs); color: var(--primary); font-weight: 700;">Judge Arena &rarr;</a>
                </div>

                @if(isset($judgeProblems) && $judgeProblems->count() > 0)
                    <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                        @foreach($judgeProblems as $problem)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: var(--space-3); background: var(--bg-surface-hover); border: 1px solid var(--glass-border); border-radius: 12px; gap: var(--space-3);">
                                <div style="min-width: 0;">
                                    <div style="font-weight: 700; font-size: var(--font-size-sm); color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $problem->title }}
                                    </div>
                                    <div style="font-size: var(--font-size-xs); color: var(--text-muted);">
                                        Time Limit: {{ $problem->time_limit ?? 1 }}s • Memory: {{ $problem->memory_limit ?? 256 }}MB
                                    </div>
                                </div>
                                <a href="{{ route('judge.show', $problem->id) }}" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: var(--font-size-xs); flex-shrink: 0;">
                                    Solve
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state-card">
                        <p style="font-size: var(--font-size-sm); color: var(--text-muted); margin: 0;">No active judge problems found.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: My Recent Submissions & Workstation Guidelines -->
        <div style="display: flex; flex-direction: column; gap: var(--space-5);">
            <!-- My Recent Submissions Card -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">My Recent Submissions</h3>
                        <p class="card-subtitle">Your uploaded assignments & solutions</p>
                    </div>
                </div>

                @if(isset($mySubmissions) && $mySubmissions->count() > 0)
                    <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                        @foreach($mySubmissions as $sub)
                            <div style="padding: var(--space-3); background: var(--bg-surface-hover); border: 1px solid var(--glass-border); border-radius: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <strong style="font-size: var(--font-size-sm); color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $sub->file_name }}
                                    </strong>
                                    <span class="badge badge-success">SUBMITTED</span>
                                </div>
                                <div style="font-size: var(--font-size-xs); color: var(--text-muted); display: flex; justify-content: space-between;">
                                    <span>Task: {{ $sub->labTask->title ?? 'General' }}</span>
                                    <span>{{ $sub->created_at ? $sub->created_at->diffForHumans() : 'Recently' }}</span>
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ route('labs.submit') }}" class="btn btn-secondary" style="width: 100%; margin-top: var(--space-2); font-size: var(--font-size-xs);">
                            + Submit Another File
                        </a>
                    </div>
                @else
                    <div class="empty-state-card" style="padding: var(--space-6) var(--space-4);">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--color-accent-light); color: var(--color-accent); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-3);">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h4 style="font-size: var(--font-size-sm); font-weight: 700; color: var(--text-main); margin-bottom: 4px;">No Submissions Yet</h4>
                        <p style="font-size: var(--font-size-xs); color: var(--text-muted); line-height: 1.5; margin-bottom: var(--space-4);">
                            Once you solve a task or finish your Packet Tracer file, upload it here to get a verified receipt.
                        </p>
                        <a href="{{ route('labs.submit') }}" class="btn btn-accent" style="font-size: var(--font-size-xs);">
                            Submit First Solution &rarr;
                        </a>
                    </div>
                @endif
            </div>

            <!-- Offline Workstation Quick Guide Card -->
            <div class="card" style="border-left: 4px solid var(--primary);">
                <h3 class="card-title" style="margin-bottom: var(--space-3); font-size: var(--font-size-base);">Lab Instructions & Guidelines</h3>
                <ul style="padding-left: var(--space-4); font-size: var(--font-size-xs); color: var(--text-muted); display: flex; flex-direction: column; gap: var(--space-2); line-height: 1.5;">
                    <li><strong>100% Offline LAN:</strong> Everything runs locally on the university master lab network. No WAN or internet required.</li>
                    <li><strong>Instant Verdicts:</strong> Judge submissions are automatically compiled and evaluated in sandbox containers locally.</li>
                    <li><strong>Naming Standard:</strong> Label your submission files clearly (e.g. <code>[StudentID]_[TaskName].pkt</code>) before uploading.</li>
                </ul>
            </div>
        </div>
    </div>

</div>
@endif

<style>
.live-dot {
    width: 8px;
    height: 8px;
    background-color: #10b981;
    border-radius: 50%;
    display: inline-block;
    position: relative;
    vertical-align: middle;
}

.live-dot::after {
    content: "";
    position: absolute;
    inset: -2px;
    border-radius: 50%;
    border: 2px solid #10b981;
    animation: liveDotPulse 2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
    pointer-events: none;
    will-change: transform, opacity;
    transform: translateZ(0);
}

@keyframes liveDotPulse {
    0% { transform: scale(0.6); opacity: 1; }
    100% { transform: scale(2.2); opacity: 0; }
}

.chart-point {
    cursor: pointer;
    transition: r 0.15s ease, stroke-width 0.15s ease;
}

.chart-point:hover {
    r: 6.5px !important;
    stroke-width: 3px !important;
}
</style>

<script>
    // Live Monitoring Telemetry Poller (Pure local LAN vanilla JS - Zero external dependencies)
    (function() {
        let lastKnownActivityId = 0;
        const activityContainer = document.getElementById('dashboardActivityList');
        const tooltip = document.getElementById('chartTooltip');

        function attachChartTooltips() {
            if (!tooltip) return;
            document.querySelectorAll('.chart-point').forEach(circle => {
                circle.onmouseenter = function() {
                    const val = this.getAttribute('data-val');
                    const lbl = this.getAttribute('data-lbl');
                    tooltip.textContent = `${val} PC${val == 1 ? '' : 's'} active at ${lbl}`;
                    tooltip.style.display = 'block';
                };
                circle.onmouseleave = function() {
                    tooltip.style.display = 'none';
                };
            });
        }

        let lastChartJson = '';
        // 1. Live Telemetry Polling (Every 3 seconds)
        function fetchLiveTelemetry() {
            fetch("{{ route('telemetry.live') }}")
                .then(res => res.json())
                .then(data => {
                    if (!data) return;

                    // Sync Chart SVG Paths & Data (Only redraw when telemetry changes)
                    if (data.chart) {
                        const currentChartJson = JSON.stringify(data.chart);
                        if (currentChartJson !== lastChartJson) {
                            lastChartJson = currentChartJson;

                            const areaPath = document.getElementById('chartAreaPath');
                            const linePath = document.getElementById('chartLinePath');
                            if (areaPath && data.chart.areaPath) areaPath.setAttribute('d', data.chart.areaPath);
                            if (linePath && data.chart.linePath) linePath.setAttribute('d', data.chart.linePath);

                            // Update Circles (Deep Teal + Coral Orange Peak)
                            const circlesGroup = document.getElementById('chartCirclesGroup');
                            if (circlesGroup && data.chart.circles) {
                                let circlesHtml = '';
                                data.chart.circles.forEach(c => {
                                    if (c.isPeak) {
                                        circlesHtml += `<circle cx="${c.cx}" cy="${c.cy}" r="5.5" fill="#FF5E3A" stroke="#ffffff" stroke-width="2.5" class="chart-point" data-val="${c.value}" data-lbl="${c.label}" />`;
                                    } else {
                                        circlesHtml += `<circle cx="${c.cx}" cy="${c.cy}" r="4" fill="#ffffff" stroke="#0D5C5E" stroke-width="2.5" class="chart-point" data-val="${c.value}" data-lbl="${c.label}" />`;
                                    }
                                });
                                circlesGroup.innerHTML = circlesHtml;
                                attachChartTooltips();
                            }

                            // Update Peak Badge
                            const peakRect = document.getElementById('chartPeakRect');
                            const peakText = document.getElementById('chartPeakText');
                            if (peakRect && peakText) {
                                peakRect.setAttribute('x', data.chart.peakRectX);
                                peakRect.setAttribute('y', data.chart.peakRectY);
                                if (data.chart.peakRectWidth) peakRect.setAttribute('width', data.chart.peakRectWidth);
                                peakText.setAttribute('x', data.chart.peakTextX);
                                peakText.setAttribute('y', data.chart.peakTextY);
                                peakText.textContent = data.chart.peakLabel;
                            }

                            // Update X Axis
                            const xAxisGroup = document.getElementById('chartXAxisGroup');
                            if (xAxisGroup && data.chart.circles) {
                                let xHtml = '';
                                data.chart.circles.forEach(c => {
                                    const peakAttr = c.isPeak ? 'font-weight="600"' : '';
                                    const peakSuffix = c.isPeak ? ' (Peak)' : '';
                                    xHtml += `<text x="${c.cx}" y="165" fill="var(--text-muted)" font-size="11" text-anchor="middle" ${peakAttr}>${c.label}${peakSuffix}</text>`;
                                });
                                xAxisGroup.innerHTML = xHtml;
                            }

                            // Update Y Axis
                            if (data.chart.yLabels) {
                                const y0 = document.getElementById('chartY0');
                                const y1 = document.getElementById('chartY1');
                                const y2 = document.getElementById('chartY2');
                                if (y0) y0.textContent = data.chart.yLabels[0];
                                if (y1) y1.textContent = data.chart.yLabels[1];
                                if (y2) y2.textContent = data.chart.yLabels[2];
                            }
                        }
                    }

                    // Sync Connected Clients Card with Live Data (Only update if value changed)
                    if (data.stats && data.stats.connected_clients) {
                        const clientVal = document.getElementById('statConnectedClients');
                        const clientPct = document.getElementById('statConnectedClientsPercentage');
                        const newClientText = `${data.stats.connected_clients.current} / ${data.stats.connected_clients.total}`;
                        const newPctText = `${data.stats.connected_clients.percentage}%`;
                        if (clientVal && clientVal.textContent.trim() !== newClientText) clientVal.textContent = newClientText;
                        if (clientPct && clientPct.textContent.trim() !== newPctText) clientPct.textContent = newPctText;
                    }

                    // Sync System Infrastructure Cards
                    if (data.systemStatus && Array.isArray(data.systemStatus)) {
                        data.systemStatus.forEach(item => {
                            const slug = item.title.toLowerCase().replace(/[^a-z0-9]+/g, '-');
                            const v = document.getElementById('val-' + slug);
                            const d = document.getElementById('desc-' + slug);
                            if (v) v.textContent = item.value;
                            if (d) d.textContent = item.desc;
                        });
                    }
                })
                .catch(() => {});
        }

        // 2. Workstation Keep-Alive Heartbeat (Every 15s)
        function sendClientHeartbeat() {
            fetch("{{ route('client.heartbeat') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    hostname: 'MASTER-PC',
                    user: 'Administrator',
                    action: 'Master Dashboard Active'
                })
            }).catch(() => {});
        }

        // 3. Live Activities Polling (Every 4s)
        function fetchLiveActivities() {
            if (!activityContainer) return;
            fetch("{{ route('activities.stream') }}?limit=8")
                .then(res => res.json())
                .then(data => {
                    if (!data.activities || data.activities.length === 0) return;
                    if (data.latest_id === lastKnownActivityId && lastKnownActivityId !== 0) return;
                    lastKnownActivityId = data.latest_id;

                    const icons = {
                        user: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>`,
                        app: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>`,
                        file: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" /></svg>`,
                        backup: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>`,
                        system: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>`
                    };

                    let html = '';
                    data.activities.forEach(act => {
                        const iconSvg = icons[act.icon] || icons.system;
                        const msg = act.details ? `${act.action} - ${act.details}` : act.action;
                        const ipBadge = act.client_ip ? `<span class="badge badge-neutral" style="font-family: monospace; font-size: 0.65rem;">${act.client_ip}</span>` : '';

                        html += `
                            <div class="activity-item" data-id="${act.id}">
                                <div class="activity-icon ${act.icon}">${iconSvg}</div>
                                <div class="activity-details">
                                    <span class="activity-message">${msg}</span>
                                    <div style="display: flex; align-items: center; gap: var(--space-2); margin-top: 2px;">
                                        ${ipBadge}
                                        <span class="activity-time">${act.time}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    activityContainer.innerHTML = html;
                })
                .catch(() => {});
        }

        // Initialize and start intervals
        attachChartTooltips();
        fetchLiveTelemetry();
        sendClientHeartbeat();

        setInterval(fetchLiveTelemetry, 3000);
        setInterval(fetchLiveActivities, 4000);
        setInterval(sendClientHeartbeat, 15000);
    })();
</script>
@else
<script>
    // Student Workstation Heartbeat (LAN status check)
    (function() {
        function sendClientHeartbeat() {
            fetch("{{ route('client.heartbeat') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    hostname: 'STUDENT-WORKSTATION',
                    user: '{{ auth()->user()->name ?? "Student" }}',
                    action: 'Student Lab Workspace Active'
                })
            }).catch(() => {});
        }
        sendClientHeartbeat();
        setInterval(sendClientHeartbeat, 20000);
    })();
</script>
@endif
@endsection
