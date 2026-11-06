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
        </div>

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
                                <td style="padding: var(--space-3); font-weight: 600;">{{ $problem->title }}</td>
                                <td style="padding: var(--space-3);">{{ $problem->time_limit }} sec</td>
                                <td style="padding: var(--space-3);">{{ $problem->memory_limit }} MB</td>
                                <td style="padding: var(--space-3);">{{ $problem->test_cases_count }}</td>
                                <td style="padding: var(--space-3); text-align: right;">
                                    <a class="btn btn-primary" href="{{ route('judge.show', $problem->id) }}">Solve Problem</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
