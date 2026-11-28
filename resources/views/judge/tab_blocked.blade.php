@extends('layouts.app')

@section('title', 'Access Blocked — Tab Policy Violation')

@section('content')
<section class="card" role="alert" style="max-width: 720px; margin: var(--space-8) auto; border-color: var(--color-danger-text);">
    <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-4);">
        <span class="badge badge-danger" style="font-size: 0.75rem;">SECURITY LOCK ACTIVE</span>
        @if(isset($latestViolation) && $latestViolation->created_at)
            <span style="font-size: var(--font-size-xs); color: var(--text-muted);">
                Recorded {{ $latestViolation->created_at->format('M d, Y h:i A') }}
            </span>
        @endif
    </div>

    <h1 style="margin-top: 0; color: var(--color-danger-text); font-size: var(--font-size-xl);">
        Access Blocked due to Browser Tab Policy Violation
    </h1>

    <p style="color: var(--text-main); line-height: 1.6;">
        Your access to <strong>Lab Tasks, File Downloads, Solution Submissions, and the Local Programming Judge</strong> has been blocked because an unauthorized browser tab or tab switch was detected on your workstation.
    </p>

    @if(isset($latestViolation) && $latestViolation->details)
        <div style="padding: var(--space-3); background: var(--color-danger-bg); border-radius: var(--radius-md); margin-bottom: var(--space-4); font-size: var(--font-size-xs); color: var(--color-danger-text);">
            <strong>Recorded Reason:</strong> {{ $latestViolation->details }}
        </div>
    @endif

    <p style="color: var(--text-muted); font-size: var(--font-size-sm);">
        This incident has been reported to the Master Lab Administrator. Please contact your instructor or lab administrator to review and restore your account access.
    </p>

    <div style="margin-top: var(--space-5);">
        <a class="btn btn-primary" href="{{ route('dashboard') }}">Return to Dashboard</a>
    </div>
</section>
@endsection
