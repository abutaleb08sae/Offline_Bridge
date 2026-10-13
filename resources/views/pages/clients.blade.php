@extends('layouts.app')

@section('title', 'Clients Management')

@section('content')
<!-- TODO: Connect real LAN socket pinging & client heartbeat discovery agent -->
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Connected Workstations (LAN Clients)</h3>
                <p class="card-subtitle">Real-time monitoring of lab student PCs communicating over local subnet</p>
            </div>
            <button class="btn btn-primary" onclick="showToast('LAN Scan Initiated', 'Ping broadcast sent to 192.168.1.0/24 subnet', 'info')">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                <span>Scan Subnet LAN</span>
            </button>
        </div>

        <!-- Filter Tabs -->
        <div class="tab-container">
            <button class="tab-btn active" onclick="filterClients('ALL', this)">All Workstations (6)</button>
            <button class="tab-btn" onclick="filterClients('online', this)">Online (4)</button>
            <button class="tab-btn" onclick="filterClients('idle', this)">Idle (1)</button>
            <button class="tab-btn" onclick="filterClients('offline', this)">Offline (1)</button>
        </div>

        <!-- Clients Table -->
        <div class="table-container">
            <table class="table" id="clientsTable">
                <thead>
                    <tr>
                        <th>Workstation Hostname</th>
                        <th>IP Address</th>
                        <th>MAC Address</th>
                        <th>Active User</th>
                        <th>Running Application</th>
                        <th>Status</th>
                        <th>Last Seen</th>
                        <th style="text-align: right;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $c)
                        <tr data-status="{{ $c['status'] }}">
                            <td style="font-weight: 600; font-family: monospace;">{{ $c['hostname'] }}</td>
                            <td><code style="background: var(--bg-surface-hover); padding: 0.15rem 0.35rem; border-radius: 4px;">{{ $c['ip'] }}</code></td>
                            <td style="font-family: monospace; font-size: var(--font-size-xs); color: var(--text-muted);">{{ $c['mac'] }}</td>
                            <td style="font-size: var(--font-size-xs);">{{ $c['user'] }}</td>
                            <td style="font-size: var(--font-size-xs);">{{ $c['app'] }}</td>
                            <td>
                                @if($c['status'] === 'online')
                                    <span class="badge badge-success">ONLINE</span>
                                @elseif($c['status'] === 'idle')
                                    <span class="badge badge-warning">IDLE</span>
                                @else
                                    <span class="badge badge-neutral">OFFLINE</span>
                                @endif
                            </td>
                            <td style="font-size: var(--font-size-xs); color: var(--text-muted);">{{ $c['last_seen'] }}</td>
                            <td style="text-align: right;">
                                <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" onclick="viewClientDetail('{{ $c['hostname'] }}', '{{ $c['ip'] }}', '{{ $c['mac'] }}', '{{ $c['user'] }}')">
                                    View Specs
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Client Detail Drawer Modal -->
<div class="modal-backdrop" id="clientDetailModal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalClientHostname">Workstation Details</h3>
            <button class="modal-close-btn" onclick="closeModal('clientDetailModal')" aria-label="Close modal">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-2);">
                    <span style="color: var(--text-muted);">Workstation Hostname:</span>
                    <strong id="modalDetailHost" style="font-family: monospace;">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-2);">
                    <span style="color: var(--text-muted);">Assigned IP Address:</span>
                    <strong id="modalDetailIp" style="font-family: monospace; color: var(--primary);">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-2);">
                    <span style="color: var(--text-muted);">Hardware MAC:</span>
                    <strong id="modalDetailMac" style="font-family: monospace;">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-2);">
                    <span style="color: var(--text-muted);">Active Logged User:</span>
                    <strong id="modalDetailUser">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">Installed Apps:</span>
                    <strong>Cisco Packet Tracer v8.2, Wireshark v4.0</strong>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('clientDetailModal')">Close</button>
            <button type="button" class="btn btn-primary" onclick="closeModal('clientDetailModal'); showToast('Ping Sent', 'Sent diagnostic echo request to host', 'info');">Ping Host</button>
        </div>
    </div>
</div>

<script>
    function filterClients(status, btn) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const rows = document.querySelectorAll('#clientsTable tbody tr');
        rows.forEach(r => {
            if (status === 'ALL' || r.getAttribute('data-status') === status) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    }

    function viewClientDetail(hostname, ip, mac, user) {
        document.getElementById('modalClientHostname').textContent = 'Workstation: ' + hostname;
        document.getElementById('modalDetailHost').textContent = hostname;
        document.getElementById('modalDetailIp').textContent = ip;
        document.getElementById('modalDetailMac').textContent = mac;
        document.getElementById('modalDetailUser').textContent = user;
        openModal('clientDetailModal');
    }
</script>
@endsection
