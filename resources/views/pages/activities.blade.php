@extends('layouts.app')

@section('title', 'Activities & Monitoring')

@section('content')
<!-- TODO: Connect real ActivityLogger audit trail database subscriber -->
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Live System Activities Stream</h3>
                <p class="card-subtitle">Chronological audit trail of student events, application launches, and system operations</p>
            </div>
            <button class="btn btn-secondary" onclick="showToast('Activity Log Cleared', 'Display list refreshed', 'info')">
                Refresh Stream
            </button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="filter-bar">
            <div class="search-input-wrapper">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                <input type="text" id="activitySearchInput" class="form-input" placeholder="Filter activities by keyword..." onkeyup="filterActivities()">
            </div>

            <div style="min-width: 180px;">
                <select id="activityCategorySelect" class="form-select" onchange="filterActivities()">
                    <option value="ALL">All Categories</option>
                    <option value="Users">Users</option>
                    <option value="Applications">Applications</option>
                    <option value="Files">Files</option>
                    <option value="System">System</option>
                </select>
            </div>
        </div>

        <!-- Timeline Stream -->
        <div class="activity-list" id="activityStreamList">
            @foreach($activities as $act)
                <div class="activity-item" data-category="{{ $act['category'] }}">
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
                        <div style="display: flex; gap: var(--space-3); align-items: center; margin-top: 0.15rem;">
                            <span class="badge badge-neutral" style="font-size: 0.65rem;">Category: {{ $act['category'] }}</span>
                            <span class="activity-time">{{ $act['time'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

<script>
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
