@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<!-- TODO: Connect config persistence file/database save handler in SettingsController -->
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <form onsubmit="event.preventDefault(); showToast('Settings Saved', 'System configuration updated successfully (UI Demo)', 'success');">
        
        <!-- Network Settings Card -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-header">
                <div>
                    <h3 class="card-title">LAN Network Settings</h3>
                    <p class="card-subtitle">Local Master PC network interface & subnet parameters</p>
                </div>
                <span class="badge badge-success">Interface Active</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label" for="masterIp">Master Server IP Address</label>
                    <input type="text" id="masterIp" class="form-input" value="{{ $settings['network']['master_ip'] }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="httpPort">HTTP Server Port</label>
                    <input type="number" id="httpPort" class="form-input" value="{{ $settings['network']['http_port'] }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subnetMask">Subnet Mask</label>
                    <input type="text" id="subnetMask" class="form-input" value="{{ $settings['network']['subnet_mask'] }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="gateway">Default Gateway IP</label>
                    <input type="text" id="gateway" class="form-input" value="{{ $settings['network']['gateway'] }}" required>
                </div>
            </div>
        </div>

        <!-- Security & Storage Policy Settings Card -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Security & Storage Policy</h3>
                    <p class="card-subtitle">Session limits, password rules, and upload file size quotas</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label" for="sessionTimeout">Student Session Timeout (Minutes)</label>
                    <input type="number" id="sessionTimeout" class="form-input" value="{{ $settings['security']['session_timeout'] }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="minPass">Minimum Password Length</label>
                    <input type="number" id="minPass" class="form-input" value="{{ $settings['security']['password_min_length'] }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="maxFileSize">Max File Upload Limit (MB)</label>
                    <input type="number" id="maxFileSize" class="form-input" value="{{ $settings['security']['max_file_size'] }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="allowedExt">Allowed File Extensions</label>
                    <input type="text" id="allowedExt" class="form-input" value="{{ $settings['security']['allowed_extensions'] }}" required>
                </div>
            </div>
        </div>

        <!-- General System Settings Card -->
        <div class="card" style="margin-bottom: var(--space-6);">
            <div class="card-header">
                <div>
                    <h3 class="card-title">General Lab Details</h3>
                    <p class="card-subtitle">Master Host labeling and automatic database backup schedule</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label" for="labName">Lab Facility Name</label>
                    <input type="text" id="labName" class="form-input" value="{{ $settings['general']['lab_name'] }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="masterHost">Master PC Hostname</label>
                    <input type="text" id="masterHost" class="form-input" value="{{ $settings['general']['master_host'] }}" required>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="autoBackup">Automated DB Backup Frequency</label>
                    <select id="autoBackup" class="form-select">
                        <option value="daily" selected>Daily Automated Backup (At 02:00 AM)</option>
                        <option value="hourly">Hourly Backup Snapshot</option>
                        <option value="weekly">Weekly Backup Archive</option>
                        <option value="disabled">Disabled (Manual Only)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Form Submit Bar -->
        <div style="display: flex; justify-content: flex-end; gap: var(--space-3);">
            <button type="reset" class="btn btn-secondary">Reset Changes</button>
            <button type="submit" class="btn btn-primary">Save System Settings</button>
        </div>
    </form>

</div>
@endsection
