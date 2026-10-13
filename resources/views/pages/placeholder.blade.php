@extends('layouts.app')

@section('title', $title ?? 'Page')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">

    <div class="coming-soon-card">
        <div class="coming-soon-icon">
            <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 01-3.586 0 2.548 2.548 0 010-3.586l5.653-4.655m3.116-2.023a4.5 4.5 0 10-6.364-6.364 4.5 4.5 0 006.364 6.364zm0 0l2.122-2.122" />
            </svg>
        </div>
        <h2 class="coming-soon-title">{{ $title }}</h2>
        <p class="coming-soon-desc">{{ $subtitle }}</p>

        <div style="display: flex; gap: var(--space-3);">
            <span class="badge badge-info">Step 1 Shell Only</span>
            <span class="badge badge-neutral">Logic Coming Soon</span>
        </div>
    </div>

</div>
@endsection
