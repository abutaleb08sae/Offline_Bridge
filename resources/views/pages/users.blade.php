@extends('layouts.app')

@section('title', 'Users Management')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <!-- Success / Error Alert Messages -->
    @if(session('success'))
        <div class="card" style="background-color: var(--color-success-bg); border-color: rgba(16, 185, 129, 0.4); padding: var(--space-4);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-3);">
                <div style="display: flex; align-items: center; gap: var(--space-3);">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px; color: var(--color-success-text); flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span style="font-weight: 600; color: var(--color-success-text); font-size: var(--font-size-sm);">
                        {{ session('success') }}
                    </span>
                </div>
                <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.5rem; font-size: var(--font-size-xs);" onclick="this.closest('.card').remove()">
                    Dismiss
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="card" style="background-color: var(--color-danger-bg); border-color: rgba(239, 68, 68, 0.4); padding: var(--space-4);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-3);">
                <div style="display: flex; align-items: center; gap: var(--space-3);">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px; color: var(--color-danger-text); flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span style="font-weight: 600; color: var(--color-danger-text); font-size: var(--font-size-sm);">
                        {{ session('error') }}
                    </span>
                </div>
                <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.5rem; font-size: var(--font-size-xs);" onclick="this.closest('.card').remove()">
                    Dismiss
                </button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="card" style="background-color: var(--color-danger-bg); border-color: rgba(239, 68, 68, 0.4); padding: var(--space-4);">
            <div style="display: flex; align-items: flex-start; gap: var(--space-3);">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px; color: var(--color-danger-text); flex-shrink: 0; margin-top: 2px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <div style="flex: 1;">
                    <strong style="color: var(--color-danger-text); font-size: var(--font-size-sm); display: block; margin-bottom: var(--space-1);">
                        Please resolve the following input issues:
                    </strong>
                    <ul style="margin: 0; padding-left: var(--space-4); color: var(--color-danger-text); font-size: var(--font-size-xs); line-height: 1.5;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Header Actions & Search Filter Bar -->
    <div class="card">
        <div class="card-header" style="margin-bottom: 0; padding-bottom: 0; border: none;">
            <div>
                <h3 class="card-title">Registered Lab Users</h3>
                <p class="card-subtitle">Manage student accounts, instructor credentials, and access roles on Master PC</p>
            </div>
            <button class="btn btn-primary" onclick="openModal('addUserModal')">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                <span>Add New User</span>
            </button>
        </div>

        <div style="margin-top: var(--space-4);" class="filter-bar">
            <!-- Search Box -->
            <div class="search-input-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                <input type="text" id="userSearchInput" class="form-input" placeholder="Search user by name or email..." onkeyup="filterUsersTable()">
            </div>

            <!-- Role Filter Select -->
            <div style="min-width: 180px;">
                <select id="roleFilterSelect" class="form-select" onchange="filterUsersTable()">
                    <option value="ALL">All Roles</option>
                    <option value="ADMIN">ADMIN</option>
                    <option value="INSTRUCTOR">INSTRUCTOR</option>
                    <option value="STUDENT">STUDENT</option>
                    <option value="USER">USER</option>
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
                        <th style="text-align: right; width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $roleName = strtolower($user->role ?? 'user');
                            $roleDisplay = strtoupper($user->role ?? 'USER');
                            $isActive = strtolower($user->status ?? 'active') === 'active';
                        @endphp
                        <tr data-role="{{ $roleDisplay }}">
                            <td style="font-weight: 600;">
                                {{ $user->name }}
                                @if(auth()->id() === $user->id)
                                    <span class="badge badge-info" style="font-size: 0.65rem; margin-left: var(--space-1);">You</span>
                                @endif
                            </td>
                            <td><code style="font-size: var(--font-size-xs); background: var(--bg-surface-hover); padding: 0.15rem 0.35rem; border-radius: 4px;">{{ $user->email }}</code></td>
                            <td>
                                @if($roleName === 'admin')
                                    <span class="badge badge-danger">ADMIN</span>
                                @elseif($roleName === 'instructor')
                                    <span class="badge badge-info">INSTRUCTOR</span>
                                @elseif($roleName === 'student')
                                    <span class="badge badge-neutral">STUDENT</span>
                                @else
                                    <span class="badge badge-neutral">{{ $roleDisplay }}</span>
                                @endif
                            </td>
                            <td>
                                @if($isActive)
                                    <span class="badge badge-success">ACTIVE</span>
                                @else
                                    <span class="badge badge-neutral" style="background-color: var(--color-danger-bg); color: var(--color-danger-text);">INACTIVE</span>
                                @endif
                            </td>
                            <td style="font-size: var(--font-size-xs); color: var(--text-muted);">
                                @if($user->last_login_at)
                                    <span title="{{ $user->last_login_at->format('M d, Y h:i A') }}">
                                        {{ $user->last_login_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span style="color: var(--text-muted); opacity: 0.7;">Never</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: var(--space-2);">
                                    <!-- Edit Button -->
                                    <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" 
                                        onclick="openEditUserModal({{ json_encode([
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'role' => $user->role ?? 'user',
                                            'status' => $user->status ?? 'active'
                                        ]) }})">
                                        Edit
                                    </button>

                                    <!-- Delete Button Form -->
                                    @if(auth()->id() === $user->id)
                                        <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs); opacity: 0.5; cursor: not-allowed;" title="You cannot delete your own admin account" disabled>
                                            Delete
                                        </button>
                                    @else
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete user \'{{ addslashes($user->name) }}\' ({{ $user->email }})?\n\nThis will remove their account permanently.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" title="Delete User">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: var(--space-6); color: var(--text-muted);">
                                No users found in database. Click "Add New User" to register users.
                            </td>
                        </tr>
                    @endforelse
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
            <button type="button" class="modal-close-btn" onclick="closeModal('addUserModal')" aria-label="Close modal">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="addUserName">Full Name <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" name="name" id="addUserName" class="form-input" placeholder="e.g. Abdullah Al Mamun" required value="{{ old('name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="addUserEmail">Email Address <span style="color: var(--color-danger);">*</span></label>
                    <input type="email" name="email" id="addUserEmail" class="form-input" placeholder="e.g. student@cse.univ.edu" required value="{{ old('email') }}">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="form-group">
                        <label class="form-label" for="addUserRole">User Role <span style="color: var(--color-danger);">*</span></label>
                        <select name="role" id="addUserRole" class="form-select" required>
                            <option value="student" {{ old('role') === 'student' ? 'selected' : '' }}>STUDENT</option>
                            <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>USER</option>
                            <option value="instructor" {{ old('role') === 'instructor' ? 'selected' : '' }}>INSTRUCTOR</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>ADMIN</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="addUserStatus">Account Status <span style="color: var(--color-danger);">*</span></label>
                        <select name="status" id="addUserStatus" class="form-select" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>ACTIVE</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>INACTIVE</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="addUserPassword">Initial Password <span style="color: var(--color-danger);">*</span></label>
                    <input type="password" name="password" id="addUserPassword" class="form-input" placeholder="At least 8 characters" required minlength="8">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-backdrop" id="editUserModal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit User Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('editUserModal')" aria-label="Close modal">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <form id="editUserForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="editUserName">Full Name <span style="color: var(--color-danger);">*</span></label>
                    <input type="text" name="name" id="editUserName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="editUserEmail">Email Address <span style="color: var(--color-danger);">*</span></label>
                    <input type="email" name="email" id="editUserEmail" class="form-input" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div class="form-group">
                        <label class="form-label" for="editUserRole">User Role <span style="color: var(--color-danger);">*</span></label>
                        <select name="role" id="editUserRole" class="form-select" required>
                            <option value="student">STUDENT</option>
                            <option value="user">USER</option>
                            <option value="instructor">INSTRUCTOR</option>
                            <option value="admin">ADMIN</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="editUserStatus">Account Status <span style="color: var(--color-danger);">*</span></label>
                        <select name="status" id="editUserStatus" class="form-select" required>
                            <option value="active">ACTIVE</option>
                            <option value="inactive">INACTIVE</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="editUserPassword">
                        New Password <span style="font-weight: 400; color: var(--text-muted);">(Optional - leave blank to keep unchanged)</span>
                    </label>
                    <input type="password" name="password" id="editUserPassword" class="form-input" placeholder="Leave empty to keep existing password" minlength="8">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
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

    function openEditUserModal(user) {
        const form = document.getElementById('editUserForm');
        // Set update action route dynamically
        form.action = "{{ url('/users') }}/" + user.id;

        document.getElementById('editUserName').value = user.name || '';
        document.getElementById('editUserEmail').value = user.email || '';
        document.getElementById('editUserRole').value = (user.role || 'user').toLowerCase();
        document.getElementById('editUserStatus').value = (user.status || 'active').toLowerCase();
        document.getElementById('editUserPassword').value = '';

        openModal('editUserModal');
    }
</script>
@endsection
