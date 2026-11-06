@extends('layouts.app')

@section('title', 'Judge Submission Details')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-4);">
    <a href="{{ route('judge.admin.submissions') }}" style="color: var(--primary); text-decoration: none;">&larr; All submissions</a>

    <section class="card">
        <h2 style="margin-top: 0;">Submission #{{ $submission->id }}</h2>
        <dl style="display: grid; grid-template-columns: max-content 1fr; gap: var(--space-2) var(--space-4);">
            <dt>User</dt><dd>{{ $submission->user?->name ?? 'Guest' }}{{ $submission->user_id ? ' (ID ' . $submission->user_id . ')' : '' }}</dd>
            <dt>Problem</dt><dd>{{ $submission->problem->title }}</dd>
            <dt>Language</dt><dd>{{ $submission->language === 'cpp' ? 'C++' : 'Python' }}</dd>
            <dt>Submitted</dt><dd>{{ $submission->created_at->format('Y-m-d H:i:s') }}</dd>
            <dt>Verdict</dt><dd>{{ $submission->verdict }}</dd>
        </dl>
    </section>

    <section class="card">
        <h3 style="margin-top: 0;">Submitted code</h3>
        <pre style="white-space: pre-wrap; overflow-wrap: anywhere; overflow-x: auto;"><code>{{ $submission->code }}</code></pre>
    </section>

    @if($submission->error_details)
        <section class="card">
            <h3 style="margin-top: 0;">Execution details</h3>
            <pre style="white-space: pre-wrap; overflow-wrap: anywhere; overflow-x: auto;">{{ $submission->error_details }}</pre>
        </section>
    @endif
</div>
@endsection
