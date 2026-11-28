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
                <h2 class="card-title">Browser Tab Policy Violations</h2>
                <p class="card-subtitle">Students blocked from Lab Tasks and Judge after opening unauthorized browser tabs or switching tabs.</p>
            </div>
            <span class="badge {{ ($activeTabBlockCount ?? 0) > 0 ? 'badge-danger' : 'badge-success' }}">
                {{ $activeTabBlockCount ?? 0 }} blocked {{ \Illuminate\Support\Str::plural('student', $activeTabBlockCount ?? 0) }}
            </span>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name &amp; ID</th>
                        <th>Violation</th>
                        <th>Page / Details</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tabViolations ?? collect() as $tabViolation)
                        <tr>
                            <td>
                                {{ $tabViolation->user?->name ?? 'Deleted user' }}
                                <span style="display: block; color: var(--text-muted);">ID {{ $tabViolation->user_id }}</span>
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $tabViolation->violation_type)) }}</td>
                            <td>
                                @if($tabViolation->page_url)
                                    <code style="font-size: var(--font-size-xs);">{{ $tabViolation->page_url }}</code>
                                @endif
                                @if($tabViolation->details)
                                    <span style="display: block; font-size: var(--font-size-xs); color: var(--text-muted);">{{ $tabViolation->details }}</span>
                                @endif
                            </td>
                            <td>{{ $tabViolation->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @if($tabViolation->is_blocked)
                                    <span class="badge badge-danger">Blocked</span>
                                @else
                                    <span class="badge badge-neutral">Unblocked</span>
                                @endif
                            </td>
                            <td>
                                @if($tabViolation->is_blocked)
                                    <form action="{{ route('admin.tab-violations.unblock', $tabViolation) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: var(--font-size-xs); border-radius: 999px;">Unblock Student</button>
                                    </form>
                                @else
                                    <span style="color: var(--text-muted);">No action needed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: var(--space-6); color: var(--text-muted);">
                                No browser tab policy violations have been recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Student Copy-Paste Permissions Matrix</h2>
                <p class="card-subtitle">Global ON permits copy-paste on every problem. When global is OFF, turn ON individual problem exceptions.</p>
            </div>
        </div>

        <div style="padding: 0 var(--space-4) var(--space-4);">
            <label for="student-permission-search" class="form-label">Search students</label>
            <input id="student-permission-search" type="search" class="form-input" placeholder="Search student by name or email..." autocomplete="off">
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Global Copy-Paste</th>
                        <th>Summary &amp; Actions</th>
                    </tr>
                </thead>
                <tbody id="student-permission-list">
                    @forelse($students as $student)
                        @php
                            $activeExceptions = $student->copyPastePermissions->where('is_allowed', true)->count();
                            $blockedProblems = $student->problemBlocks->unique('problem_id');
                            $blockedProblemCount = $blockedProblems->count();
                            $problemPanelId = 'student-problems-'.$student->id;
                        @endphp
                        <tr data-student-row data-search="{{ \Illuminate\Support\Str::lower($student->name.' '.$student->email) }}">
                            <td>
                                <div style="display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap;">
                                    <strong>{{ $student->name }}</strong>
                                    <span style="color: var(--text-muted); font-size: var(--font-size-xs);">ID {{ $student->id }} · {{ $student->email }}</span>
                                </div>
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
                                <div style="display: flex; align-items: center; gap: var(--space-2); flex-wrap: wrap;">
                                    <span class="badge badge-neutral">{{ $activeExceptions }} {{ $activeExceptions === 1 ? 'Problem Exception ON' : 'Problem Exceptions ON' }}</span>
                                    @if($blockedProblemCount > 0)
                                        <span class="badge badge-danger">{{ $blockedProblemCount }} Blocked</span>
                                    @endif
                                    <button type="button" class="btn btn-secondary" style="padding: 0.35rem 0.7rem; font-size: var(--font-size-xs); border-radius: 999px;"
                                        data-details-toggle aria-controls="{{ $problemPanelId }}" aria-expanded="false">
                                        Manage Problems <span data-chevron aria-hidden="true">▾</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr id="{{ $problemPanelId }}" data-details-row hidden>
                            <td colspan="3" style="background: var(--surface-muted, #f8fafc);">
                                <div style="display: flex; flex-direction: column; gap: var(--space-3); padding: var(--space-2);">
                                    @if($student->allow_copy_paste)
                                        <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">Global Copy-Paste is currently ON (all problems allowed). Turn Global OFF to restrict specific problems.</p>
                                    @endif

                                    @if($problems->isNotEmpty())
                                        <div>
                                            <label for="problem-search-{{ $student->id }}" class="form-label">Filter problems</label>
                                            <input id="problem-search-{{ $student->id }}" type="search" class="form-input" placeholder="Search problems..." autocomplete="off" data-problem-filter>
                                        </div>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--space-2);">
                                            @foreach($problems as $problem)
                                                @php
                                                    $permission = $student->copyPastePermissions->firstWhere('problem_id', $problem->id);
                                                    $isAllowed = $permission?->is_allowed ?? false;
                                                    $problemBlock = $student->problemBlocks->firstWhere('problem_id', $problem->id);
                                                @endphp
                                                <div data-problem-card data-problem-search="{{ \Illuminate\Support\Str::lower($problem->title) }}" style="display: flex; flex-direction: column; gap: var(--space-2); padding: 0.65rem; border: 1px solid var(--glass-border); border-radius: var(--radius-md); background: var(--surface, #fff);">
                                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-2);">
                                                        <span style="font-size: var(--font-size-sm);">{{ $problem->title }}</span>
                                                        <form action="{{ route('admin.copy-paste-control.permissions.toggle', [$student, $problem]) }}" method="POST">
                                                            @csrf
                                                            <button type="submit"
                                                                class="badge {{ $isAllowed ? 'badge-success' : 'badge-neutral' }}"
                                                                style="border: 0; cursor: pointer; font-family: inherit; {{ $isAllowed ? '' : 'background-color: var(--color-danger-bg); color: var(--color-danger-text);' }}"
                                                                aria-label="{{ $isAllowed ? 'Restrict' : 'Allow' }} copy-paste for {{ $student->name }} on {{ $problem->title }}"
                                                                aria-pressed="{{ $isAllowed ? 'true' : 'false' }}">
                                                                {{ $isAllowed ? 'ON' : 'OFF' }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @if($problemBlock)
                                                        <div style="display: flex; align-items: center; gap: var(--space-2);">
                                                            <span class="badge badge-danger">BLOCKED</span>
                                                            <form action="{{ route('admin.copy-paste-control.unblock', $problemBlock) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: var(--font-size-xs); border-radius: 999px;">Unblock</button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        <p data-no-problem-results hidden style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">No problems match your search.</p>
                                    @else
                                        <span style="color: var(--text-muted);">No problems available.</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: var(--space-6); color: var(--text-muted);">
                                No non-admin student accounts found.
                            </td>
                        </tr>
                    @endforelse
                    @if($students->isNotEmpty())
                        <tr data-no-student-results hidden>
                            <td colspan="3" style="text-align: center; padding: var(--space-6); color: var(--text-muted);">No students match your search.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const studentSearch = document.getElementById('student-permission-search');
        const studentRows = Array.from(document.querySelectorAll('[data-student-row]'));
        const noStudentResults = document.querySelector('[data-no-student-results]');

        document.querySelectorAll('[data-details-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const panel = document.getElementById(button.getAttribute('aria-controls'));
                const isOpen = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', String(!isOpen));
                panel.hidden = isOpen;
                button.querySelector('[data-chevron]').textContent = isOpen ? '▾' : '▴';
            });
        });

        if (studentSearch) {
            studentSearch.addEventListener('input', function () {
                const search = studentSearch.value.trim().toLocaleLowerCase();
                let visibleCount = 0;

                studentRows.forEach(function (row) {
                    const matches = row.dataset.search.includes(search);
                    const detailsRow = row.nextElementSibling;
                    row.hidden = !matches;
                    detailsRow.hidden = !matches || row.querySelector('[data-details-toggle]').getAttribute('aria-expanded') !== 'true';

                    if (!matches) {
                        const toggle = row.querySelector('[data-details-toggle]');
                        toggle.setAttribute('aria-expanded', 'false');
                        toggle.querySelector('[data-chevron]').textContent = '▾';
                    }

                    if (matches) {
                        visibleCount += 1;
                    }
                });

                if (noStudentResults) {
                    noStudentResults.hidden = visibleCount > 0;
                }
            });
        }

        document.querySelectorAll('[data-problem-filter]').forEach(function (input) {
            input.addEventListener('input', function () {
                const search = input.value.trim().toLocaleLowerCase();
                const panel = input.closest('[data-details-row]');
                const cards = Array.from(panel.querySelectorAll('[data-problem-card]'));
                let visibleCount = 0;

                cards.forEach(function (card) {
                    const matches = card.dataset.problemSearch.includes(search);
                    card.hidden = !matches;
                    if (matches) {
                        visibleCount += 1;
                    }
                });

                const noResults = panel.querySelector('[data-no-problem-results]');
                if (noResults) {
                    noResults.hidden = visibleCount > 0;
                }
            });
        });
    });
</script>
@endsection
