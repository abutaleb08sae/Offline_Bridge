@extends('layouts.app')

@section('title', 'Judge Submissions')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-4);">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
        <div>
            <h2 style="margin: 0; color: var(--text-main);">Judge Submissions</h2>
            <p style="color: var(--text-muted);">Review student code and judge execution details.</p>
        </div>
        <a href="{{ route('judge.admin.problems.create') }}" class="btn btn-primary">Create problem</a>
    </div>

    <div class="card table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
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
                        <td>{{ $submission->problem->title }}</td>
                        <td>{{ $submission->language === 'cpp' ? 'C++' : 'Python' }}</td>
                        <td><span class="badge {{ $verdictClass }}">{{ $submission->verdict }}</span></td>
                        <td>{{ $submission->created_at->format('Y-m-d H:i:s') }}</td>
                        <td><a class="btn btn-secondary" href="{{ route('judge.admin.submissions.show', $submission->id) }}">View details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No judge submissions have been received.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding: var(--space-3);">{{ $submissions->links() }}</div>
    </div>
</div>
@endsection
