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
            @if(auth()->check() && auth()->user()->isAdmin())
                <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
                    <a class="btn btn-secondary" href="{{ route('judge.admin.problems.create') }}#problemImport">Import via URL</a>
                    <a class="btn btn-primary" href="{{ route('judge.admin.problems.create') }}">Create Problem</a>
                    <a class="btn btn-secondary" href="{{ route('admin.copy-paste-control') }}" style="display: inline-flex; align-items: center; gap: var(--space-2); border-radius: 999px;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 3v5c0 5-3.4 8.3-8 10-4.6-1.7-8-5-8-10V6l8-3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12l1.7 1.7 3.5-3.7" />
                        </svg>
                        <span>Copy-Paste &amp; Block Control</span>
                        @php($totalBlocks = ($activeBlockCount ?? 0) + ($activeTabBlockCount ?? 0))
                        @if($totalBlocks > 0)
                            <span class="badge badge-danger" style="padding: 0.15rem 0.4rem; font-size: 0.65rem;" aria-label="{{ $totalBlocks }} active {{ \Illuminate\Support\Str::plural('block', $totalBlocks) }}" title="{{ $totalBlocks }} active {{ \Illuminate\Support\Str::plural('block', $totalBlocks) }}">{{ $totalBlocks }}</span>
                        @endif
                    </a>
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
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Problem</th>
                            <th>Time limit</th>
                            <th>Memory limit</th>
                            <th>Test cases</th>
                            <th>PDF</th>
                            <th style="text-align: right;"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($problems as $problem)
                            <tr>
                                <td style="font-weight: 600;">
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
                                <td>{{ $problem->time_limit }} sec</td>
                                <td>{{ $problem->memory_limit }} MB</td>
                                <td>{{ $problem->test_cases_count }}</td>
                                <td>
                                    <span class="badge {{ $problem->pdf_path ? 'badge-success' : 'badge-neutral' }}">
                                        {{ $problem->pdf_path ? 'PDF Ready' : 'No PDF' }}
                                    </span>
                                    @if($problem->pdf_path)
                                        <a class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" href="{{ route('judge.problem.pdf', $problem->id) }}" target="_blank" rel="noopener" data-allow-new-tab="true">PDF</a>
                                        @if(auth()->check() && auth()->user()->isAdmin())
                                            <form method="POST" action="{{ route('judge.admin.problems.media.pdf.remove', $problem->id) }}" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: var(--font-size-xs);" type="submit">Remove PDF</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: var(--space-2); align-items: center;">
                                        <a class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: var(--font-size-xs);" href="{{ route('judge.show', $problem->id) }}">Solve Problem</a>
                                        @if(auth()->check() && auth()->user()->isAdmin())
                                            <a class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: var(--font-size-xs);" href="{{ route('judge.problems.edit', $problem->id) }}">Edit</a>
                                            <form method="POST"
                                                  action="{{ route('judge.admin.problems.destroy', $problem->id) }}"
                                                  onsubmit="return confirm('Are you sure you want to delete this problem and all its test cases?');"
                                                  style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger" style="padding: 0.35rem 0.75rem; font-size: var(--font-size-xs);" type="submit">Delete</button>
                                            </form>
                                        @endif
                                    </div>
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
