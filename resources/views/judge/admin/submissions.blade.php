@extends('layouts.app')

@section('title', 'Judge Submissions')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-4);">
    @if(session('success'))
        <div class="card" role="status" style="border-color: var(--color-success-text); color: var(--color-success-text);">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
        <div>
            <h2 style="margin: 0; color: var(--text-main);">Judge Submissions</h2>
            <p style="color: var(--text-muted);">Review student code and judge execution details.</p>
        </div>
        <div style="display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap;">
            <a href="{{ route('judge.admin.problems.create') }}" class="btn btn-primary">Create problem</a>
            <a href="{{ route('admin.copy-paste-control') }}" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: var(--space-2); border-radius: 999px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 3v5c0 5-3.4 8.3-8 10-4.6-1.7-8-5-8-10V6l8-3z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12l1.7 1.7 3.5-3.7" />
                </svg>
                <span>Copy-Paste &amp; Block Control</span>
                @if($activeBlockCount > 0)
                    <span class="badge badge-danger" style="padding: 0.15rem 0.4rem; font-size: 0.65rem;" aria-label="{{ $activeBlockCount }} active {{ \Illuminate\Support\Str::plural('block', $activeBlockCount) }}" title="{{ $activeBlockCount }} active {{ \Illuminate\Support\Str::plural('block', $activeBlockCount) }}">{{ $activeBlockCount }}</span>
                @endif
            </a>
        </div>
    </div>

    <div class="card table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>COPY-PASTE</th>
                    <th>Problem</th>
                    <th>Language</th>
                    <th>Verdict</th>
                    <th>Submitted</th>
                    <th><span class="sr-only">Action</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                    @php
                        $verdictClass = match ($submission->verdict) {
                            'Accepted' => 'badge-success',
                            'Wrong Answer', 'Compilation Error', 'Time Limit Exceeded', 'Security Violation', 'Runtime Error', 'Output Limit Exceeded' => 'badge-danger',
                            default => 'badge-warning',
                        };
                    @endphp
                    <tr>
                        <td>#{{ $submission->id }}</td>
                        <td>{{ $submission->user?->name ?? 'Guest' }}{{ $submission->user_id ? ' (ID ' . $submission->user_id . ')' : '' }}</td>
                        <td>
                            @if($submission->user && strtolower($submission->user->role ?? '') !== 'admin')
                                <form action="{{ route('admin.users.toggle-copy-paste', $submission->user_id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit"
                                        class="badge {{ $submission->user->allow_copy_paste ? 'badge-success' : 'badge-neutral' }}"
                                        style="border: 0; cursor: pointer; font-family: inherit; {{ $submission->user->allow_copy_paste ? '' : 'background-color: var(--color-danger-bg); color: var(--color-danger-text);' }}"
                                        aria-label="{{ $submission->user->allow_copy_paste ? 'Disable' : 'Enable' }} copy-paste for {{ $submission->user->name }}"
                                        aria-pressed="{{ $submission->user->allow_copy_paste ? 'true' : 'false' }}">
                                        {{ $submission->user->allow_copy_paste ? 'ON' : 'OFF' }}
                                    </button>
                                </form>
                            @else
                                <span class="badge badge-neutral">—</span>
                            @endif
                        </td>
                        <td>{{ $submission->problem->title }}</td>
                        <td>{{ $submission->language === 'cpp' ? 'C++' : 'Python' }}</td>
                        <td><span class="badge {{ $verdictClass }}">{{ $submission->verdict }}</span></td>
                        <td>{{ $submission->created_at->format('Y-m-d H:i:s') }}</td>
                        <td style="white-space: nowrap;">
                            <a class="btn btn-secondary" href="{{ route('judge.admin.submissions.show', $submission->id) }}">View details</a>
                            @if($submission->active_problem_block_id)
                                <form action="{{ route('admin.problem-blocks.unblock', $submission->active_problem_block_id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs); border-radius: 999px;" aria-label="Unblock {{ $submission->user?->name }} from {{ $submission->problem->title }}">Unblock</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No judge submissions have been received.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding: var(--space-3);">{{ $submissions->links() }}</div>
    </div>
</div>
@endsection
