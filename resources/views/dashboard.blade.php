@extends('layouts.app')

@section('title', 'Master Dashboard Overview')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <!-- Section 1: System Status Cards Row -->
    <div>
        <h2 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: var(--space-3);">
            System Infrastructure Status
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-4);">
            @foreach($systemStatus as $item)
                <div class="card" style="padding: var(--space-4);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-2);">
                        <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase;">
                            {{ $item['title'] }}
                        </span>
                        @if($item['type'] === 'success')
                            <span class="badge badge-success">{{ $item['badge'] }}</span>
                        @elseif($item['type'] === 'warning')
                            <span class="badge badge-warning">{{ $item['badge'] }}</span>
                        @elseif($item['type'] === 'danger')
                            <span class="badge badge-danger">{{ $item['badge'] }}</span>
                        @else
                            <span class="badge badge-neutral" style="background-color: rgba(37, 99, 235, 0.15); color: #3b82f6;">{{ $item['badge'] }}</span>
                        @endif
                    </div>
                    <div style="font-size: var(--font-size-2xl); font-weight: 700; color: var(--text-main); margin-bottom: 0.125rem;">
                        {{ $item['value'] }}
                    </div>
                    <div style="font-size: var(--font-size-xs); color: var(--text-muted);">
                        {{ $item['desc'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Section 2: Metrics Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-4);">
        <!-- Connected Clients -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Connected Clients</span>
                    <div style="font-size: var(--font-size-2xl); font-weight: 700; margin-top: 0.25rem;">
                        {{ $stats['connected_clients']['current'] }} / {{ $stats['connected_clients']['total'] }}
                    </div>
                </div>
                <div style="width: 40px; height: 40px; background-color: var(--color-success-bg); color: var(--color-success-text); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 11.25v-6" />
                    </svg>
                </div>
            </div>
            <div style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted);">
                <span style="color: var(--color-success-text); font-weight: 600;">{{ $stats['connected_clients']['percentage'] }}%</span> lab capacity occupied
            </div>
        </div>

        <!-- Registered Users -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Registered Users</span>
                    <div style="font-size: var(--font-size-2xl); font-weight: 700; margin-top: 0.25rem;">
                        {{ $stats['registered_users']['total'] }}
                    </div>
                </div>
                <div style="width: 40px; height: 40px; background-color: var(--primary-light); color: var(--primary); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
            </div>
            <div style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted);">
                {{ $stats['registered_users']['students'] }} Students, {{ $stats['registered_users']['instructors'] }} Instructors
            </div>
        </div>

        <!-- Supported Applications -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Supported Apps</span>
                    <div style="font-size: var(--font-size-2xl); font-weight: 700; margin-top: 0.25rem;">
                        {{ $stats['supported_apps']['total'] }} Packages
                    </div>
                </div>
                <div style="width: 40px; height: 40px; background-color: var(--color-info-bg); color: var(--color-info-text); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </div>
            </div>
            <div style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted);">
                1 Active Session running
            </div>
        </div>

        <!-- Files Stored -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Files Stored</span>
                    <div style="font-size: var(--font-size-2xl); font-weight: 700; margin-top: 0.25rem;">
                        {{ $stats['files_stored']['count'] }} Files
                    </div>
                </div>
                <div style="width: 40px; height: 40px; background-color: var(--color-warning-bg); color: var(--color-warning-text); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
            </div>
            <div style="margin-top: var(--space-3); font-size: var(--font-size-xs); color: var(--text-muted);">
                Total Storage: {{ $stats['files_stored']['size'] }}
            </div>
        </div>
    </div>

    <!-- Section 6 & Section 7 Grid: Chart + Quick Actions -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6);">
        <!-- Section 6: Client Connections Lightweight SVG Chart -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Client Connections Over 12 Hours</h3>
                    <p class="card-subtitle">Real-time workstation activity on the local Master network</p>
                </div>
                <span class="badge badge-success">Live Monitoring</span>
            </div>

            <!-- Custom Clean Responsive Inline SVG Chart -->
            <div style="width: 100%; overflow-x: auto; padding-top: var(--space-2);">
                <svg viewBox="0 0 650 200" style="width: 100%; height: auto; display: block; overflow: visible;">
                    <defs>
                        <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb" stop-opacity="0.35"/>
                            <stop offset="100%" stop-color="#2563eb" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>

                    <!-- Horizontal Grid Lines -->
                    <line x1="40" y1="20" x2="630" y2="20" stroke="var(--border-color)" stroke-dasharray="3,3" />
                    <line x1="40" y1="60" x2="630" y2="60" stroke="var(--border-color)" stroke-dasharray="3,3" />
                    <line x1="40" y1="100" x2="630" y2="100" stroke="var(--border-color)" stroke-dasharray="3,3" />
                    <line x1="40" y1="140" x2="630" y2="140" stroke="var(--border-color)" stroke-dasharray="3,3" />

                    <!-- Y-Axis Labels -->
                    <text x="30" y="24" fill="var(--text-muted)" font-size="11" text-anchor="end">30</text>
                    <text x="30" y="64" fill="var(--text-muted)" font-size="11" text-anchor="end">20</text>
                    <text x="30" y="104" fill="var(--text-muted)" font-size="11" text-anchor="end">10</text>
                    <text x="30" y="144" fill="var(--text-muted)" font-size="11" text-anchor="end">0</text>

                    <!-- Area Fill Path -->
                    <path d="M 50,134.6 L 140,124 L 230,90.6 L 320,72.6 L 410,77.3 L 500,97.3 L 590,117.3 L 590,140 L 50,140 Z" fill="url(#chartGradient)" />

                    <!-- Smooth Trend Line Path -->
                    <path d="M 50,134.6 L 140,124 L 230,90.6 L 320,72.6 L 410,77.3 L 500,97.3 L 590,117.3" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                    <!-- Interactive Data Points -->
                    <circle cx="50" cy="134.6" r="4" fill="#ffffff" stroke="#2563eb" stroke-width="2.5" />
                    <circle cx="140" cy="124" r="4" fill="#ffffff" stroke="#2563eb" stroke-width="2.5" />
                    <circle cx="230" cy="90.6" r="4" fill="#ffffff" stroke="#2563eb" stroke-width="2.5" />
                    <circle cx="320" cy="72.6" r="5" fill="#2563eb" stroke="#ffffff" stroke-width="2" />
                    <circle cx="410" cy="77.3" r="4" fill="#ffffff" stroke="#2563eb" stroke-width="2.5" />
                    <circle cx="500" cy="97.3" r="4" fill="#ffffff" stroke="#2563eb" stroke-width="2.5" />
                    <circle cx="590" cy="117.3" r="4" fill="#ffffff" stroke="#2563eb" stroke-width="2.5" />

                    <!-- Peak Value Callout -->
                    <rect x="295" y="45" width="50" height="20" rx="4" fill="#2563eb" />
                    <text x="320" y="59" fill="#ffffff" font-size="10" font-weight="700" text-anchor="middle">28 PCs</text>

                    <!-- X-Axis Labels -->
                    <text x="50" y="165" fill="var(--text-muted)" font-size="11" text-anchor="middle">08:00</text>
                    <text x="140" y="165" fill="var(--text-muted)" font-size="11" text-anchor="middle">10:00</text>
                    <text x="230" y="165" fill="var(--text-muted)" font-size="11" text-anchor="middle">12:00</text>
                    <text x="320" y="165" fill="var(--text-muted)" font-size="11" font-weight="600" text-anchor="middle">14:00 (Peak)</text>
                    <text x="410" y="165" fill="var(--text-muted)" font-size="11" text-anchor="middle">16:00</text>
                    <text x="500" y="165" fill="var(--text-muted)" font-size="11" text-anchor="middle">18:00</text>
                    <text x="590" y="165" fill="var(--text-muted)" font-size="11" text-anchor="middle">20:00</text>
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
                <button class="btn btn-secondary" style="justify-content: flex-start; padding: var(--space-3) var(--space-4);">
                    <svg style="width:20px;height:20px;color:var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.105a6.375 6.375 0 0112.75 0v.105" />
                    </svg>
                    <span>Add New Student / User</span>
                </button>

                <!-- Register Application -->
                <button class="btn btn-secondary" style="justify-content: flex-start; padding: var(--space-3) var(--space-4);">
                    <svg style="width:20px;height:20px;color:var(--color-info-text);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Register Application Package</span>
                </button>

                <!-- Upload File -->
                <button class="btn btn-secondary" style="justify-content: flex-start; padding: var(--space-3) var(--space-4);">
                    <svg style="width:20px;height:20px;color:var(--color-success-text);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <span>Upload Lab Material / File</span>
                </button>

                <!-- Create Backup -->
                <button class="btn btn-secondary" style="justify-content: flex-start; padding: var(--space-3) var(--space-4);">
                    <svg style="width:20px;height:20px;color:var(--color-warning-text);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    <span>Create Offline DB Backup</span>
                </button>
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
            <a href="{{ route('applications.index') }}" class="btn btn-secondary" style="font-size: var(--font-size-xs);">View All Apps</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-4);">
            @foreach($applications as $app)
                <div style="background-color: var(--bg-surface-hover); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: var(--space-4); display: flex; flex-direction: column; justify-content: space-between;">
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

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: var(--space-3); margin-top: var(--space-2);">
                        <span style="font-size: var(--font-size-xs); color: var(--text-muted);">
                            Active Installs: <strong style="color: var(--text-main);">{{ $app['installed_clients'] }} PCs</strong>
                        </span>
                        <button class="btn btn-secondary" style="padding: 0.25rem 0.625rem; font-size: var(--font-size-xs);">Configure</button>
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
                <a href="{{ route('clients.index') }}" class="btn btn-secondary" style="font-size: var(--font-size-xs);">Manage Clients</a>
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
                                <td><code style="background: var(--bg-surface-hover); padding: 0.15rem 0.35rem; border-radius: 4px;">{{ $client['ip'] }}</code></td>
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
                </div>

                <div class="activity-list">
                    @foreach($activities as $act)
                        <div class="activity-item">
                            <div class="activity-icon {{ $act['icon'] }}">
                                @if($act['icon'] === 'user')
                                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>
                                @elseif($act['icon'] === 'app')
                                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                                @elseif($act['icon'] === 'file')
                                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" /></svg>
                                @elseif($act['icon'] === 'backup')
                                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                                @else
                                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                @endif
                            </div>
                            <div class="activity-details">
                                <span class="activity-message">{{ $act['message'] }}</span>
                                <span class="activity-time">{{ $act['time'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Section 8: Network Info Card -->
            <div class="card" style="background: linear-gradient(135deg, var(--bg-surface), var(--bg-surface-hover));">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Master Network Details</h3>
                        <p class="card-subtitle">Server Host Configuration</p>
                    </div>
                    <span class="badge badge-success">Active Master</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: var(--space-3); font-size: var(--font-size-xs);">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px stroke var(--border-color); padding-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Master IP:</span>
                        <strong style="font-family: monospace; color: var(--primary);">{{ $network['master_ip'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px stroke var(--border-color); padding-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Hostname:</span>
                        <strong style="font-family: monospace; color: var(--text-main);">{{ $network['hostname'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px stroke var(--border-color); padding-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Subnet Mask:</span>
                        <strong style="font-family: monospace; color: var(--text-main);">{{ $network['subnet_mask'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px stroke var(--border-color); padding-bottom: 0.5rem;">
                        <span style="color: var(--text-muted);">Server Port:</span>
                        <strong style="font-family: monospace; color: var(--text-main);">{{ $network['server_port'] }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px stroke var(--border-color); padding-bottom: 0.5rem;">
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
@endsection
