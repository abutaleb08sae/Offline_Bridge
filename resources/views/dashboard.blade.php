@extends('layouts.app')

@section('title', 'Master Dashboard Overview')

@section('content')
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
                <!-- Tooltip floating overlay -->
                <div id="chartTooltip" style="position: absolute; display: none; pointer-events: none; background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; top: 10px; right: 10px; box-shadow: 0 4px 14px rgba(0,0,0,0.3); border: 1px solid rgba(255, 255, 255, 0.15);"></div>

                <svg id="chartSvg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 650 200" style="width: 100%; height: auto; display: block; overflow: visible;">
                    <defs>
                        <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb" stop-opacity="0.38"/>
                            <stop offset="100%" stop-color="#2563eb" stop-opacity="0.0"/>
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

                    <!-- Smooth Trend Line Path -->
                    <path id="chartLinePath" d="{{ $chartData['linePath'] }}" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="transition: d 0.4s ease;" />

                    <!-- Dynamic Interactive Data Points -->
                    <g id="chartCirclesGroup">
                        @foreach($chartData['circles'] as $c)
                            @if($c['isPeak'])
                                <circle cx="{{ $c['cx'] }}" cy="{{ $c['cy'] }}" r="5" fill="#2563eb" stroke="#ffffff" stroke-width="2" class="chart-point" data-val="{{ $c['value'] }}" data-lbl="{{ $c['label'] }}" />
                            @else
                                <circle cx="{{ $c['cx'] }}" cy="{{ $c['cy'] }}" r="4" fill="#ffffff" stroke="#2563eb" stroke-width="2.5" class="chart-point" data-val="{{ $c['value'] }}" data-lbl="{{ $c['label'] }}" />
                            @endif
                        @endforeach
                    </g>

                    <!-- Peak Value Callout -->
                    <g id="chartPeakGroup">
                        <rect id="chartPeakRect" x="{{ $chartData['peakRectX'] }}" y="{{ $chartData['peakRectY'] }}" width="{{ $chartData['peakRectWidth'] ?? 54 }}" height="20" rx="4" fill="#2563eb" />
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

        // 1. Live Telemetry Polling (Every 3 seconds)
        function fetchLiveTelemetry() {
            fetch("{{ route('telemetry.live') }}")
                .then(res => res.json())
                .then(data => {
                    if (!data) return;

                    // Sync Chart SVG Paths & Data
                    if (data.chart) {
                        const areaPath = document.getElementById('chartAreaPath');
                        const linePath = document.getElementById('chartLinePath');
                        if (areaPath && data.chart.areaPath) areaPath.setAttribute('d', data.chart.areaPath);
                        if (linePath && data.chart.linePath) linePath.setAttribute('d', data.chart.linePath);

                        // Update Circles
                        const circlesGroup = document.getElementById('chartCirclesGroup');
                        if (circlesGroup && data.chart.circles) {
                            let circlesHtml = '';
                            data.chart.circles.forEach(c => {
                                if (c.isPeak) {
                                    circlesHtml += `<circle cx="${c.cx}" cy="${c.cy}" r="5" fill="#2563eb" stroke="#ffffff" stroke-width="2" class="chart-point" data-val="${c.value}" data-lbl="${c.label}" />`;
                                } else {
                                    circlesHtml += `<circle cx="${c.cx}" cy="${c.cy}" r="4" fill="#ffffff" stroke="#2563eb" stroke-width="2.5" class="chart-point" data-val="${c.value}" data-lbl="${c.label}" />`;
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

                    // Sync Connected Clients Card with Live Data
                    if (data.stats && data.stats.connected_clients) {
                        const clientVal = document.getElementById('statConnectedClients');
                        const clientPct = document.getElementById('statConnectedClientsPercentage');
                        if (clientVal) clientVal.textContent = `${data.stats.connected_clients.current} / ${data.stats.connected_clients.total}`;
                        if (clientPct) clientPct.textContent = `${data.stats.connected_clients.percentage}%`;
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
@endsection
