@extends('layouts.app')

@section('title', 'Activities & Monitoring')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Live System Activities & Audit Stream</h3>
                <p class="card-subtitle">Real-time audit trail of client workstation actions, downloads, submissions, and system operations</p>
            </div>
            <div style="display: flex; align-items: center; gap: var(--space-3);">
                <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--font-size-xs);">
                    <span style="display: inline-block; width: 8px; height: 8px; background-color: #10b981; border-radius: 50%;"></span>
                    Offline LAN Sync Active
                </span>
                <button class="btn btn-secondary" style="font-size: var(--font-size-xs);" onclick="fetchActivities(true)">
                    Refresh Now
                </button>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="filter-bar">
            <div class="search-input-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" id="activitySearchInput" class="form-input" placeholder="Filter activities by action, IP, or details..." onkeyup="filterActivities()">
            </div>

            <div style="min-width: 180px;">
                <select id="activityCategorySelect" class="form-select" onchange="filterActivities()">
                    <option value="ALL">All Categories</option>
                    <option value="Users">Users & Workstations</option>
                    <option value="Files">Files & Submissions</option>
                    <option value="Applications">Applications</option>
                    <option value="System">System & Database</option>
                </select>
            </div>
        </div>

        <!-- Timeline Stream -->
        <div class="activity-list" id="activityStreamList">
            @forelse($activities as $act)
                <div class="activity-item" data-id="{{ $act['id'] ?? '' }}" data-category="{{ $act['category'] ?? 'System' }}">
                    <div class="activity-icon {{ $act['icon'] ?? 'system' }}">
                        @if(($act['icon'] ?? '') === 'user')
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>
                        @elseif(($act['icon'] ?? '') === 'app')
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                        @elseif(($act['icon'] ?? '') === 'file')
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" /></svg>
                        @elseif(($act['icon'] ?? '') === 'backup')
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        @endif
                    </div>
                    <div class="activity-details" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-2);">
                            <span class="activity-message" style="font-weight: 600; color: var(--text-main);">
                                {{ $act['message'] ?? $act['action'] }}
                            </span>
                            @if(!empty($act['status']))
                                @if($act['status'] === 'success')
                                    <span class="badge badge-success" style="font-size: 0.65rem;">SUCCESS</span>
                                @elseif($act['status'] === 'warning')
                                    <span class="badge badge-warning" style="font-size: 0.65rem;">WARN</span>
                                @elseif($act['status'] === 'danger')
                                    <span class="badge badge-danger" style="font-size: 0.65rem;">ALERT</span>
                                @else
                                    <span class="badge badge-info" style="font-size: 0.65rem;">INFO</span>
                                @endif
                            @endif
                        </div>

                        <div style="display: flex; gap: var(--space-3); align-items: center; margin-top: 0.25rem;">
                            @if(!empty($act['client_ip']))
                                <span class="badge badge-neutral" style="font-family: monospace; font-size: 0.65rem;">
                                    IP: {{ $act['client_ip'] }}
                                </span>
                            @endif
                            <span class="badge badge-neutral" style="font-size: 0.65rem;">
                                {{ $act['category'] ?? 'System' }}
                            </span>
                            <span class="activity-time" style="font-size: var(--font-size-xs); color: var(--text-muted);">
                                {{ $act['time'] ?? 'Just now' }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: var(--space-6); color: var(--text-muted);">
                    No activity logs recorded yet.
                </div>
            @endforelse
        </div>
    </div>

</div>

<!-- Real-time Polling & Filter Logic (Zero External Dependencies) -->
<script>
    const streamContainer = document.getElementById('activityStreamList');
    let latestActivityId = 0;

    const iconsMap = {
        user: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>`,
        app: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>`,
        file: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" /></svg>`,
        backup: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>`,
        system: `<svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>`
    };

    function fetchActivities(manual = false) {
        fetch("{{ route('activities.stream') }}?limit=50")
            .then(res => res.json())
            .then(data => {
                if (!data.activities) return;
                if (!manual && data.latest_id === latestActivityId && latestActivityId !== 0) return;
                latestActivityId = data.latest_id;

                let html = '';
                data.activities.forEach(act => {
                    const iconSvg = iconsMap[act.icon] || iconsMap.system;
                    const msg = act.details ? `${act.action} - ${act.details}` : act.action;
                    const ipBadge = act.client_ip ? `<span class="badge badge-neutral" style="font-family: monospace; font-size: 0.65rem;">IP: ${act.client_ip}</span>` : '';

                    let statusBadge = '<span class="badge badge-info" style="font-size: 0.65rem;">INFO</span>';
                    if (act.status === 'success') {
                        statusBadge = '<span class="badge badge-success" style="font-size: 0.65rem;">SUCCESS</span>';
                    } else if (act.status === 'warning') {
                        statusBadge = '<span class="badge badge-warning" style="font-size: 0.65rem;">WARN</span>';
                    } else if (act.status === 'danger') {
                        statusBadge = '<span class="badge badge-danger" style="font-size: 0.65rem;">ALERT</span>';
                    }

                    html += `
                        <div class="activity-item" data-id="${act.id}" data-category="${act.category}">
                            <div class="activity-icon ${act.icon}">${iconSvg}</div>
                            <div class="activity-details" style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-2);">
                                    <span class="activity-message" style="font-weight: 600; color: var(--text-main);">${msg}</span>
                                    ${statusBadge}
                                </div>
                                <div style="display: flex; gap: var(--space-3); align-items: center; margin-top: 0.25rem;">
                                    ${ipBadge}
                                    <span class="badge badge-neutral" style="font-size: 0.65rem;">${act.category}</span>
                                    <span class="activity-time" style="font-size: var(--font-size-xs); color: var(--text-muted);">${act.time}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                streamContainer.innerHTML = html;
                filterActivities();

                if (manual) {
                    showToast('Stream Refreshed', 'Latest LAN activity logs loaded', 'success');
                }
            })
            .catch(() => {});
    }

    // Auto-poll every 4 seconds over LAN
    setInterval(() => fetchActivities(false), 4000);

    // Filter Activities in DOM
    function filterActivities() {
        const query = document.getElementById('activitySearchInput').value.toLowerCase();
        const category = document.getElementById('activityCategorySelect').value;
        const items = document.querySelectorAll('#activityStreamList .activity-item');

        items.forEach(item => {
            const text = item.innerText.toLowerCase();
            const cat = item.getAttribute('data-category');
            const matchesQuery = text.includes(query);
            const matchesCat = (category === 'ALL' || cat === category);

            if (matchesQuery && matchesCat) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
</script>
@endsection
