@extends('layouts.app')

@section('title', 'Users Management')

@section('content')
<!-- TODO: Connect real User model & Eloquent query in UserController -->
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <!-- Header Actions & Search Filter Bar -->
    <div class="card">
        <div class="card-header" style="margin-bottom: 0; padding-bottom: 0; border: none;">
            <div>
                <h3 class="card-title">Registered Lab Users</h3>
                <p class="card-subtitle">Manage student accounts, instructor credentials, and access roles</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addUserModal')">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                <span>Add New User</span>
            </button>
        </div>

        <div style="margin-top: var(--space-4);" class="filter-bar">
            <!-- Search Box -->
            <div class="search-input-wrapper">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                <input type="text" id="userSearchInput" class="form-input" placeholder="Search user by name or email..." onkeyup="filterUsersTable()">
            </div>

            <!-- Role Filter Select -->
            <div style="min-width: 180px;">
                <select id="roleFilterSelect" class="form-select" onchange="filterUsersTable()">
                    <option value="ALL">All Roles</option>
                    <option value="ADMIN">ADMIN</option>
                    <option value="INSTRUCTOR">INSTRUCTOR</option>
                    <option value="STUDENT">STUDENT</option>
                </select>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <table class="table" id="usersTable">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr data-role="{{ $user['role'] }}">
                            <td style="font-weight: 600;">{{ $user['name'] }}</td>
                            <td><code style="font-size: var(--font-size-xs);">{{ $user['email'] }}</code></td>
                            <td>
                                @if($user['role'] === 'ADMIN')
                                    <span class="badge badge-danger">ADMIN</span>
                                @elseif($user['role'] === 'INSTRUCTOR')
                                    <span class="badge badge-info">INSTRUCTOR</span>
                                @else
                                    <span class="badge badge-neutral">STUDENT</span>
                                @endif
                            </td>
                            <td>
                                @if($user['status'] === 'ACTIVE')
                                    <span class="badge badge-success">ACTIVE</span>
                                @else
                                    <span class="badge badge-neutral">INACTIVE</span>
                                @endif
                            </td>
                            <td style="font-size: var(--font-size-xs); color: var(--text-muted);">{{ $user['last_login'] }}</td>
                            <td style="text-align: right;">
                                <!-- Edit Button UI Demo -->
                                <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" onclick="showToast('Edit User', 'Editing {{ $user['name'] }} (UI Demo Mode)', 'info')">
                                    Edit
                                </button>
                                <!-- Delete Button UI Demo -->
                                <button class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" onclick="showToast('Delete Action', 'Delete requested for {{ $user['name'] }}', 'warning')">
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

<!-- Add User Modal -->
<div class="modal-backdrop" id="addUserModal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New User</h3>
            <button class="modal-close-btn" onclick="closeModal('addUserModal')" aria-label="Close modal">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form onsubmit="event.preventDefault(); closeModal('addUserModal'); showToast('User Created', 'New user added successfully (Mock Data Demo)', 'success');">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="userName">Full Name</label>
                    <input type="text" id="userName" class="form-input" placeholder="e.g. Abdullah Al Mamun" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="userEmail">Email Address</label>
                    <input type="email" id="userEmail" class="form-input" placeholder="e.g. student@cse.univ.edu" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="userRole">User Role</label>
                    <select id="userRole" class="form-select">
                        <option value="STUDENT">STUDENT</option>
                        <option value="INSTRUCTOR">INSTRUCTOR</option>
                        <option value="ADMIN">ADMIN</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="userPassword">Default Password</label>
                    <input type="password" id="userPassword" class="form-input" value="OfflineBridge123!" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
    function filterUsersTable() {
        const query = document.getElementById('userSearchInput').value.toLowerCase();
        const role = document.getElementById('roleFilterSelect').value;
        const rows = document.querySelectorAll('#usersTable tbody tr');

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const rowRole = row.getAttribute('data-role');
            const matchesSearch = text.includes(query);
            const matchesRole = (role === 'ALL' || rowRole === role);

            if (matchesSearch && matchesRole) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endsection
