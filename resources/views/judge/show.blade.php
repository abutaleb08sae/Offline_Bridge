@extends('layouts.app')

@section('title', $problem->title)

@section('content')
@php
    $serverNow = now();
    $isUpcoming = $problem->isUpcoming($serverNow);
    $isEnded = $problem->isEnded($serverNow);
    $hasSchedule = $problem->start_time !== null || $problem->end_time !== null;
@endphp
<div style="display: flex; flex-direction: column; gap: var(--space-4);">
    <div>
        <a href="{{ route('judge.index') }}" style="color: var(--primary); text-decoration: none;">&larr; All problems</a>
    </div>

    @if($hasSchedule)
        <div class="card" id="problemSchedule"
             data-start="{{ $problem->start_time?->timestamp ? $problem->start_time->timestamp * 1000 : '' }}"
             data-end="{{ $problem->end_time?->timestamp ? $problem->end_time->timestamp * 1000 : '' }}"
             data-server-now="{{ $serverNow->timestamp * 1000 }}">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
                <span class="badge {{ $isEnded ? 'badge-warning' : ($isUpcoming ? 'badge-neutral' : 'badge-success') }}" data-schedule-status>
                    {{ $isEnded ? 'Time Expired / Contest Ended' : ($isUpcoming ? 'Upcoming' : 'Live') }}
                </span>
                <span data-schedule-countdown-wrap {{ $isUpcoming || $problem->end_time ? '' : 'hidden' }}>
                    <span data-schedule-countdown-label>{{ $isUpcoming ? 'Starts in:' : 'Time Remaining:' }}</span>
                    <strong data-schedule-countdown style="font-variant-numeric: tabular-nums;">00:00:00</strong>
                </span>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="card" role="status" style="border-color: var(--color-success-text); color: var(--color-success-text);">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="card" role="alert" style="border-color: var(--color-danger-text); color: var(--color-danger-text);">
            @foreach($errors->all() as $error)
                <p style="margin: 0 0 var(--space-1);">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if(session('judge_result'))
        @php($result = session('judge_result'))
        @php($verdictClass = $result['verdict'] === 'Accepted' ? 'badge-success' : (in_array($result['verdict'], ['Wrong Answer', 'Time Limit Exceeded'], true) ? 'badge-warning' : 'badge-danger'))
        <div class="card" role="status" style="display: flex; flex-direction: column; gap: var(--space-2);">
            <div style="display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
                <strong>Submission #{{ $result['id'] }}</strong>
                <span class="badge {{ $verdictClass }}">{{ $result['verdict'] }}</span>
            </div>
            @if($result['details'])
                <pre style="white-space: pre-wrap; overflow-wrap: anywhere; margin: 0; color: var(--text-muted);">{{ $result['details'] }}</pre>
            @endif
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr)); gap: var(--space-4); align-items: start;">
        <section class="card" aria-labelledby="problem-title">
            <div style="display: flex; justify-content: space-between; gap: var(--space-3); flex-wrap: wrap; margin-bottom: var(--space-4);">
                <h2 id="problem-title" style="margin: 0; color: var(--text-main);">{{ $problem->title }}</h2>
                <span class="badge badge-neutral">{{ $problem->time_limit }} sec / {{ $problem->memory_limit }} MB</span>
            </div>
            <div style="white-space: pre-wrap; line-height: 1.6; color: var(--text-main);">{{ $problem->description }}</div>

            @if($sample)
                <div style="margin-top: var(--space-5);">
                    <h3 style="margin-bottom: var(--space-2);">Sample input</h3>
                    <pre style="white-space: pre-wrap; overflow-wrap: anywhere; padding: var(--space-3); background: var(--bg-body); border-radius: var(--radius-md);">{{ $sample->input }}</pre>
                    <h3 style="margin: var(--space-4) 0 var(--space-2);">Sample output</h3>
                    <pre style="white-space: pre-wrap; overflow-wrap: anywhere; padding: var(--space-3); background: var(--bg-body); border-radius: var(--radius-md);">{{ $sample->expected_output }}</pre>
                </div>
            @endif
        </section>

        <section class="card" aria-labelledby="submission-title">
            <h2 id="submission-title" style="margin-top: 0;">Submit solution</h2>
            <form method="POST" action="{{ route('judge.submit') }}" style="display: flex; flex-direction: column; gap: var(--space-3);">
                @csrf
                <input type="hidden" name="judge_problem_id" value="{{ $problem->id }}">

                <label for="language" class="form-label">Language</label>
                <select id="language" name="language" class="form-select" required>
                    <option value="cpp" {{ old('language', 'cpp') === 'cpp' ? 'selected' : '' }}>C++17</option>
                    <option value="python" {{ old('language') === 'python' ? 'selected' : '' }}>Python 3</option>
                </select>

                <label for="code" class="form-label">Source code</label>
                <textarea id="code" name="code" class="form-textarea" rows="20" required maxlength="64000" spellcheck="false" autocapitalize="off" autocomplete="off" style="font-family: Consolas, 'Courier New', monospace; resize: vertical;">{{ old('code') }}</textarea>
                <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin: 0;">Maximum 64 KB. Code is sent only to a configured isolated Windows VM runner; it is never executed directly by the web server.</p>

                <button class="btn btn-primary" type="submit" data-submit-button {{ $problem->testCases->isEmpty() || $isUpcoming || $isEnded ? 'disabled' : '' }}>
                    {{ $isEnded ? 'Time Expired' : 'Submit Code' }}
                </button>
            </form>
        </section>
    </div>
</div>
@if($hasSchedule)
<script>
    (() => {
        const schedule = document.getElementById('problemSchedule');
        const start = schedule.dataset.start ? Number(schedule.dataset.start) : null;
        const end = schedule.dataset.end ? Number(schedule.dataset.end) : null;
        const status = schedule.querySelector('[data-schedule-status]');
        const countdownWrap = schedule.querySelector('[data-schedule-countdown-wrap]');
        const countdownLabel = schedule.querySelector('[data-schedule-countdown-label]');
        const countdown = schedule.querySelector('[data-schedule-countdown]');
        const serverStart = Number(schedule.dataset.serverNow);
        const clientStart = performance.now();
        const button = document.querySelector('[data-submit-button]');
        const hasTestCases = {{ $problem->testCases->isNotEmpty() ? 'true' : 'false' }};
        const format = (seconds) => {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainder = seconds % 60;
            return [hours, minutes, remainder].map((part) => String(part).padStart(2, '0')).join(':');
        };
        const update = () => {
            const now = serverStart + (performance.now() - clientStart);
            if (start !== null && now < start) {
                status.textContent = 'Upcoming';
                status.className = 'badge badge-neutral';
                countdownWrap.hidden = false;
                countdownLabel.textContent = 'Starts in:';
                countdown.textContent = format(Math.max(0, Math.floor((start - now) / 1000)));
                button.disabled = true;
                button.textContent = 'Starts Soon';
                return;
            }

            if (end !== null && now >= end) {
                status.textContent = 'Time Expired / Contest Ended';
                status.className = 'badge badge-warning';
                countdownWrap.hidden = false;
                countdownLabel.textContent = 'Time Remaining:';
                countdown.textContent = '00:00:00';
                button.disabled = true;
                button.textContent = 'Time Expired';
                return;
            }

            status.textContent = 'Live';
            status.className = 'badge badge-success';
            if (end !== null) {
                countdownWrap.hidden = false;
                countdownLabel.textContent = 'Time Remaining:';
                countdown.textContent = format(Math.max(0, Math.floor((end - now) / 1000)));
            } else {
                countdownWrap.hidden = true;
            }
            button.disabled = !hasTestCases;
            button.textContent = 'Submit Code';
        };
        update();
        window.setInterval(update, 1000);
    })();
</script>
@endif
@endsection
