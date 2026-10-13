@extends('layouts.app')

@section('title', 'Applications Management')

@section('content')
<!-- TODO: Connect Application registry database & execution sandbox runner -->
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <!-- Notice Banner -->
    <div class="card" style="background-color: var(--color-warning-bg); border-color: rgba(245, 158, 11, 0.3);">
        <div style="display: flex; align-items: flex-start; gap: var(--space-3);">
            <svg style="width:24px;height:24px;color:var(--color-warning-text);flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div>
                <strong style="color: var(--color-warning-text); font-size: var(--font-size-sm); display: block; margin-bottom: 0.15rem;">
                    Important System Rule:
                </strong>
                <p style="color: var(--color-warning-text); font-size: var(--font-size-xs); line-height: 1.4;">
                    Registering an application does not automatically make it work offline. Offline status comes from comprehensive feasibility testing and environment patching.
                </p>
            </div>
        </div>
    </div>

    <!-- Registered Applications Cards Section -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Registered Software Packages</h3>
                <p class="card-subtitle">Local lab software packages available across client PCs</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('registerAppModal')">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                <span>Register New Application</span>
            </button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-4);">
            @foreach($applications as $app)
                <div style="background-color: var(--bg-surface-hover); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: var(--space-4); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--space-2);">
                            <div>
                                <h4 style="font-size: var(--font-size-base); font-weight: 700; color: var(--text-main);">{{ $app['name'] }}</h4>
                                <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-family: monospace;">{{ $app['version'] }}</span>
                            </div>

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

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: var(--space-3);">
                        <span style="font-size: var(--font-size-xs); color: var(--text-muted);">
                            Active Installs: <strong style="color: var(--text-main);">{{ $app['installed_clients'] }} Workstations</strong>
                        </span>
                        <button class="btn btn-secondary" style="padding: 0.25rem 0.625rem; font-size: var(--font-size-xs);" onclick="showToast('App Settings', 'Configuration modal for {{ $app['name'] }}', 'info')">
                            Configure
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Application Detail & Feasibility Report Card: Cisco Packet Tracer -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Cisco Packet Tracer - Offline Compatibility Test Report</h3>
                <p class="card-subtitle">
                    Sample Feasibility Test Matrix <span style="color: var(--primary); font-weight: 600;">(SAMPLE DATA for CSE Project Demo)</span>
                </p>
            </div>
            <span class="badge badge-warning">PARTIALLY SUPPORTED</span>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Feature / Module</th>
                        <th>Internet Required?</th>
                        <th>LAN Required?</th>
                        <th>Result</th>
                        <th>Feasibility Notes & Bypass Strategy</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ptReport as $row)
                        <tr>
                            <td style="font-weight: 600;">{{ $row['feature'] }}</td>
                            <td>
                                @if($row['internet_required'])
                                    <span class="badge badge-danger">YES</span>
                                @else
                                    <span class="badge badge-neutral">NO</span>
                                @endif
                            </td>
                            <td>
                                @if($row['lan_required'])
                                    <span class="badge badge-info">YES</span>
                                @else
                                    <span class="badge badge-neutral">NO</span>
                                @endif
                            </td>
                            <td>
                                @if($row['result'] === 'PASS')
                                    <span class="badge badge-success">PASS</span>
                                @elseif($row['result'] === 'FAIL')
                                    <span class="badge badge-danger">FAIL</span>
                                @elseif($row['result'] === 'PARTIAL')
                                    <span class="badge badge-warning">PARTIAL</span>
                                @else
                                    <span class="badge badge-neutral">NOT TESTED</span>
                                @endif
                            </td>
                            <td style="font-size: var(--font-size-xs); color: var(--text-muted);">{{ $row['notes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Register Application Modal Form -->
<div class="modal-backdrop" id="registerAppModal" aria-hidden="true">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Register New Application Package</h3>
            <button class="modal-close-btn" onclick="closeModal('registerAppModal')" aria-label="Close modal">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form onsubmit="event.preventDefault(); closeModal('registerAppModal'); showToast('Application Registered', 'Software package registered in Master DB (UI Demo)', 'success');">
            <div class="modal-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                <div class="form-group" style="grid-column: span 1;">
                    <label class="form-label" for="appName">Application Name</label>
                    <input type="text" id="appName" class="form-input" placeholder="e.g. Wireshark" required>
                </div>
                <div class="form-group" style="grid-column: span 1;">
                    <label class="form-label" for="appVersion">Version</label>
                    <input type="text" id="appVersion" class="form-input" placeholder="e.g. v4.0.6" required>
                </div>
                <div class="form-group" style="grid-column: span 1;">
                    <label class="form-label" for="appCategory">Category</label>
                    <input type="text" id="appCategory" class="form-input" placeholder="e.g. Network Protocol Analysis" required>
                </div>
                <div class="form-group" style="grid-column: span 1;">
                    <label class="form-label" for="installType">Installation Type</label>
                    <select id="installType" class="form-select">
                        <option value="client">Client Application</option>
                        <option value="service">Local Service / Master Host</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="offlineStatus">Offline Status Rating</label>
                    <select id="offlineStatus" class="form-select">
                        <option value="Testing">Testing</option>
                        <option value="Supported">Supported (Fully Offline)</option>
                        <option value="Partial">Partial (Requires Bypass)</option>
                        <option value="Unsupported">Unsupported (Cloud Dependent)</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="appDescription">Description</label>
                    <textarea id="appDescription" class="form-textarea" rows="2" placeholder="Brief explanation of software utility in lab..."></textarea>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="requiredServices">Required Local Services</label>
                    <input type="text" id="requiredServices" class="form-input" placeholder="e.g. Npcap driver, local TCP port 8000">
                </div>
                <div class="form-group" style="grid-column: span 1;">
                    <label class="form-label" for="supportedFeatures">Supported Offline Features</label>
                    <textarea id="supportedFeatures" class="form-textarea" rows="2" placeholder="Features working without Internet..."></textarea>
                </div>
                <div class="form-group" style="grid-column: span 1;">
                    <label class="form-label" for="internetFeatures">Internet-Dependent Features</label>
                    <textarea id="internetFeatures" class="form-textarea" rows="2" placeholder="Features failing when offline..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('registerAppModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Register Application</button>
            </div>
        </form>
    </div>
</div>
@endsection
