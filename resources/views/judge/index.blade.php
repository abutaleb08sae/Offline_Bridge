@extends('layouts.app')

@section('title', 'Local Programming Judge')

@section('content')
<div style="display: flex; flex-direction: column; gap: var(--space-6);">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Local Programming Judge</h2>
                <p class="card-subtitle">Solve programming problems on the OfflineBridge LAN.</p>
            </div>
            @if(auth()->check() && auth()->user()->role === 'admin')
                <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
                    <a class="btn btn-secondary" href="{{ route('judge.admin.problems.create') }}#problemImport">Import via URL</a>
                    <a class="btn btn-primary" href="{{ route('judge.admin.problems.create') }}">Create Problem</a>
                </div>
            @endif
        </div>

        @if(session('success'))
            <div class="card" role="status" style="margin-bottom: var(--space-4); border-color: var(--color-success-text); color: var(--color-success-text);">
                {{ session('success') }}
            </div>
        @endif

        @if($problems->isEmpty())
            <p style="padding: var(--space-4); color: var(--text-muted);">No judge problems are available yet.</p>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border-color);">
                            <th style="padding: var(--space-3);">Problem</th>
                            <th style="padding: var(--space-3);">Time limit</th>
                            <th style="padding: var(--space-3);">Memory limit</th>
                            <th style="padding: var(--space-3);">Test cases</th>
                            <th style="padding: var(--space-3);"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($problems as $problem)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: var(--space-3); font-weight: 600;">
                                    {{ $problem->title }}
                                    @if($problem->start_time || $problem->end_time)
                                        <span class="badge {{ $problem->isUpcoming() ? 'badge-neutral' : ($problem->isEnded() ? 'badge-warning' : 'badge-success') }}"
                                              data-problem-schedule
                                              data-start="{{ $problem->start_time?->timestamp ? $problem->start_time->timestamp * 1000 : '' }}"
                                              data-end="{{ $problem->end_time?->timestamp ? $problem->end_time->timestamp * 1000 : '' }}"
                                              data-server-now="{{ now()->timestamp * 1000 }}">
                                            <span data-problem-status>{{ $problem->status() }}</span>
                                            <span data-problem-remaining {{ $problem->end_time && ! $problem->isEnded() && ! $problem->isUpcoming() ? '' : 'hidden' }}></span>
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: var(--space-3);">{{ $problem->time_limit }} sec</td>
                                <td style="padding: var(--space-3);">{{ $problem->memory_limit }} MB</td>
                                <td style="padding: var(--space-3);">{{ $problem->test_cases_count }}</td>
                                <td style="padding: var(--space-3); text-align: right;">
                                    <a class="btn btn-primary" href="{{ route('judge.show', $problem->id) }}">Solve Problem</a>
                                    @if(auth()->check() && auth()->user()->role === 'admin')
                                        <a class="btn btn-secondary" href="{{ route('judge.problems.edit', $problem->id) }}">Edit Problem</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
<script>
    (() => {
        const schedules = [...document.querySelectorAll('[data-problem-schedule]')];
        if (!schedules.length) return;
        const clientStart = performance.now();
        const serverStart = Number(schedules[0].dataset.serverNow);
        const format = (seconds) => {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainder = seconds % 60;
            return [hours, minutes, remainder].map((part) => String(part).padStart(2, '0')).join(':');
        };
        const update = () => {
            const now = serverStart + (performance.now() - clientStart);
            schedules.forEach((schedule) => {
                const start = schedule.dataset.start ? Number(schedule.dataset.start) : null;
                const end = schedule.dataset.end ? Number(schedule.dataset.end) : null;
                const status = schedule.querySelector('[data-problem-status]');
                const remaining = schedule.querySelector('[data-problem-remaining]');

                if (start !== null && now < start) {
                    status.textContent = 'Upcoming';
                    schedule.className = 'badge badge-neutral';
                    remaining.hidden = true;
                } else if (end !== null && now >= end) {
                    status.textContent = 'Ended';
                    schedule.className = 'badge badge-warning';
                    remaining.hidden = true;
                } else {
                    status.textContent = 'Live';
                    schedule.className = 'badge badge-success';
                    if (end !== null) {
                        remaining.hidden = false;
                        remaining.textContent = ` · ${format(Math.max(0, Math.floor((end - now) / 1000)))}`;
                    } else {
                        remaining.hidden = true;
                    }
                }
            });
        };
        update();
        window.setInterval(update, 1000);
    })();
</script>
@endsection
