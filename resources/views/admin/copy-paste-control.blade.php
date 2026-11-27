@extends('layouts.app')

@section('title', 'Copy-Paste & Block Control')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    @if(session('success'))
        <div class="card" role="status" style="border-color: var(--color-success-text); color: var(--color-success-text);">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="card" role="alert" style="border-color: var(--color-danger-text); color: var(--color-danger-text);">
            {{ session('error') }}
        </div>
    @endif

    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Blocked Students &amp; Violation Logs</h2>
                <p class="card-subtitle">Review policy violations and restore access for a specific student and problem.</p>
            </div>
            <span class="badge {{ $activeBlockCount > 0 ? 'badge-danger' : 'badge-success' }}">
                {{ $activeBlockCount }} active {{ \Illuminate\Support\Str::plural('block', $activeBlockCount) }}
            </span>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name &amp; ID</th>
                        <th>Blocked Problem</th>
                        <th>Violation Action</th>
                        <th>Time</th>
                        <th>Current Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($violations as $violation)
                        <tr>
                            <td>
                                {{ $violation->user?->name ?? 'Deleted user' }}
                                <span style="display: block; color: var(--text-muted);">ID {{ $violation->user_id }}</span>
                            </td>
                            <td>
                                {{ $violation->problem?->title ?? 'Deleted problem' }}
                                <span style="display: block; color: var(--text-muted);">ID {{ $violation->problem_id }}</span>
                            </td>
                            <td>{{ ucfirst($violation->violation_type) }}</td>
                            <td>{{ $violation->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @if($violation->is_blocked)
                                    <span class="badge badge-danger">Blocked</span>
                                @else
                                    <span class="badge badge-neutral">Unblocked</span>
                                @endif
                            </td>
                            <td>
                                @if($violation->is_blocked)
                                    <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
                                        <form action="{{ route('admin.copy-paste-control.unblock', $violation) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: var(--font-size-xs); border-radius: 999px;">Unblock Problem</button>
                                        </form>
                                        <form action="{{ route('admin.copy-paste-control.unblock-and-allow', $violation) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: var(--font-size-xs); border-radius: 999px;">Unblock &amp; Allow Copy-Paste</button>
                                        </form>
                                    </div>
                                @else
                                    <span style="color: var(--text-muted);">No action needed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: var(--space-6); color: var(--text-muted);">
                                No copy-paste violations have been recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding-top: var(--space-3);">{{ $violations->links() }}</div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Student Copy-Paste Permissions Matrix</h2>
                <p class="card-subtitle">Global ON permits copy-paste on every problem. When global is OFF, turn ON individual problem exceptions.</p>
            </div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Global Permission</th>
                        <th>Problem-Specific Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td style="font-weight: 600;">
                                {{ $student->name }}
                                <span style="display: block; color: var(--text-muted);">ID {{ $student->id }} · {{ $student->email }}</span>
                            </td>
                            <td>
                                <form action="{{ route('admin.users.toggle-copy-paste', $student) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="badge {{ $student->allow_copy_paste ? 'badge-success' : 'badge-neutral' }}"
                                        style="border: 0; cursor: pointer; font-family: inherit; {{ $student->allow_copy_paste ? '' : 'background-color: var(--color-danger-bg); color: var(--color-danger-text);' }}"
                                        aria-label="{{ $student->allow_copy_paste ? 'Disable' : 'Enable' }} global copy-paste for {{ $student->name }}"
                                        aria-pressed="{{ $student->allow_copy_paste ? 'true' : 'false' }}">
                                        {{ $student->allow_copy_paste ? 'ON' : 'OFF' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                @if($problems->isNotEmpty())
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-2);">
                                        @foreach($problems as $problem)
                                            @php
                                                $permission = $student->copyPastePermissions->firstWhere('problem_id', $problem->id);
                                                $isAllowed = $permission?->is_allowed ?? false;
                                            @endphp
                                            <form action="{{ route('admin.copy-paste-control.permissions.toggle', [$student, $problem]) }}" method="POST" style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-2); padding: 0.4rem 0.55rem; border: 1px solid var(--glass-border); border-radius: var(--radius-md);">
                                                @csrf
                                                <span style="font-size: var(--font-size-xs);">{{ $problem->title }}</span>
                                                <button type="submit"
                                                    class="badge {{ $isAllowed ? 'badge-success' : 'badge-neutral' }}"
                                                    style="border: 0; cursor: pointer; font-family: inherit; {{ $isAllowed ? '' : 'background-color: var(--color-danger-bg); color: var(--color-danger-text);' }}"
                                                    aria-label="{{ $isAllowed ? 'Restrict' : 'Allow' }} copy-paste for {{ $student->name }} on {{ $problem->title }}"
                                                    aria-pressed="{{ $isAllowed ? 'true' : 'false' }}">
                                                    {{ $isAllowed ? 'ON' : 'OFF' }}
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color: var(--text-muted);">No problems available.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: var(--space-6); color: var(--text-muted);">
                                No student accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
