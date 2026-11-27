@extends('layouts.app')

@section('title', $problem->title)

@section('content')
<style>
    .problem-statement { white-space: normal; }
    .problem-statement p { margin: 0 0 0.65rem; }
    .problem-statement p:empty { display: none; }
    .problem-statement ul,
    .problem-statement ol {
        margin: 0 0 var(--space-3);
        padding-left: 1.5rem;
    }
    .problem-statement ul { list-style: disc; }
    .problem-statement ol { list-style: decimal; }
    .problem-statement li { margin-bottom: var(--space-1); }
    .problem-statement pre {
        margin: 0.5rem 0 1rem;
        padding: 0.75rem 1rem;
        background: var(--bg-surface-hover);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        color: var(--text-main);
        overflow-x: auto;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }
    .problem-statement code {
        padding: 0.1em 0.3em;
        background: var(--bg-surface-hover);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-sm);
        color: var(--text-main);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        overflow-wrap: anywhere;
    }
    .problem-statement pre code { padding: 0; background: transparent; }
</style>
@php
    $serverNow = now();
    $isUpcoming = $problem->isUpcoming($serverNow);
    $isEnded = $problem->isEnded($serverNow);
    $hasSchedule = $problem->start_time !== null || $problem->end_time !== null;
    $canCopyPaste = auth()->check() && $problem->isCopyPasteAllowedFor(auth()->user());
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
        <section class="card" @if($hasPdf) aria-label="UVa problem PDF" @else aria-labelledby="problem-title" @endif>
            @if($hasPdf)
                <iframe src="{{ route('judge.problem.pdf', $problem->id) }}#toolbar=1&amp;view=FitH" title="UVa problem PDF" class="w-100" style="width: 100%; height: 82vh; border: none; border-radius: 8px;"></iframe>
            @else
                <div style="display: flex; justify-content: space-between; gap: var(--space-3); flex-wrap: wrap; margin-bottom: var(--space-4);">
                    <h2 id="problem-title" style="margin: 0; color: var(--text-main);">{{ $problem->title }}</h2>
                    <span class="badge badge-neutral">{{ $problem->time_limit }} sec / {{ $problem->memory_limit }} MB</span>
                </div>
                <div class="problem-description problem-statement" style="line-height: 1.6; color: var(--text-main);">{!! $descriptionHtml !!}</div>

                @foreach($sampleCases as $index => $sample)
                    <div style="margin-top: var(--space-5);">
                        <h3 style="margin-bottom: var(--space-2);">Sample Input {{ $index + 1 }}</h3>
                        <pre style="white-space: pre-wrap; overflow-wrap: anywhere; padding: var(--space-3); background: var(--bg-body); border-radius: var(--radius-md);">{{ $sample->input }}</pre>
                        <h3 style="margin: var(--space-4) 0 var(--space-2);">Sample Output {{ $index + 1 }}</h3>
                        <pre style="white-space: pre-wrap; overflow-wrap: anywhere; padding: var(--space-3); background: var(--bg-body); border-radius: var(--radius-md);">{{ $sample->expected_output }}</pre>
                    </div>
                @endforeach
            @endif
        </section>

        <section class="card" id="submissionEditor" aria-labelledby="submission-title">
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
                <textarea id="code" name="code" class="form-textarea" rows="20" required maxlength="64000" spellcheck="false" autocorrect="off" autocapitalize="off" autocomplete="off" style="font-family: Consolas, 'Courier New', monospace; resize: vertical;">{{ old('code') }}</textarea>
                <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin: 0;">Maximum 64 KB. Code is sent only to a configured isolated Windows VM runner; it is never executed directly by the web server.</p>

                <button class="btn btn-primary" type="submit" data-submit-button {{ $problem->testCases->isEmpty() || $isUpcoming || $isEnded ? 'disabled' : '' }}>
                    {{ $isEnded ? 'Time Expired' : 'Submit Code' }}
                </button>
            </form>
        </section>
    </div>
</div>
@if(!$canCopyPaste)
<script>
    (() => {
        const editor = document.getElementById('submissionEditor');
        const textarea = document.getElementById('code');
        if (!editor || !textarea) return;

        const warningMessage = 'Copy-paste is disabled for students. Please write your code manually.';
        let lastWarningAt = 0;
        let violationRequestStarted = false;
        let lastViolationType = null;
        let lockMessage;
        let retryButton;

        const showPasteWarning = () => {
            const now = Date.now();
            if (now - lastWarningAt < 1000) return;
            lastWarningAt = now;

            if (typeof window.showToast === 'function') {
                window.showToast('Editor restricted', warningMessage, 'warning');
                return;
            }

            const warning = document.createElement('div');
            warning.setAttribute('role', 'alert');
            warning.style.cssText = 'position:fixed;right:1rem;bottom:1rem;z-index:9999;padding:.75rem 1rem;border-radius:.5rem;background:#7f1d1d;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.25);';
            warning.textContent = warningMessage;
            document.body.appendChild(warning);
            window.setTimeout(() => warning.remove(), 3500);
        };

        const lockEditor = () => {
            textarea.readOnly = true;
            const submitButton = editor.querySelector('[data-submit-button]');
            if (submitButton) submitButton.disabled = true;
            document.querySelector('.app-container')?.setAttribute('inert', '');

            const overlay = document.createElement('div');
            overlay.setAttribute('role', 'alertdialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.style.cssText = 'position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:1.5rem;background:rgba(15,23,42,.96);color:#fff;text-align:center;';

            const panel = document.createElement('div');
            panel.style.cssText = 'max-width:36rem;padding:2rem;border:1px solid rgba(255,255,255,.2);border-radius:.75rem;background:#1e293b;';
            const heading = document.createElement('h1');
            heading.textContent = 'Access Blocked due to Copy-Paste Policy Violation';
            const message = document.createElement('p');
            message.textContent = 'Recording this policy violation. Access to this problem is being blocked.';
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'btn btn-secondary';
            retry.textContent = 'Retry recording violation';
            retry.hidden = true;
            retry.addEventListener('click', () => {
                violationRequestStarted = false;
                reportViolation(lastViolationType);
            });

            panel.append(heading, message, retry);
            overlay.appendChild(panel);
            document.body.appendChild(overlay);
            lockMessage = message;
            retryButton = retry;
        };

        const reportViolation = async (violationType) => {
            if (violationRequestStarted) return;
            violationRequestStarted = true;
            lastViolationType = violationType;
            showPasteWarning();
            if (!lockMessage) lockEditor();

            try {
                const csrfToken = textarea.form.querySelector('input[name="_token"]')?.value;
                if (!csrfToken) throw new Error('Missing request security token.');

                const response = await fetch(@json(route('judge.problems.violation')), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        problem_id: {{ $problem->id }},
                        violation_type: violationType
                    })
                });
                const result = await response.json();
                if (!response.ok || result.blocked !== true) {
                    throw new Error('The server did not confirm the block.');
                }

                window.location.replace(@json(route('judge.show', $problem->id)));
            } catch (error) {
                lockMessage.textContent = 'Unable to record the violation. This editor remains locked; retry the request to complete the block.';
                retryButton.hidden = false;
                if (typeof window.showToast === 'function') {
                    window.showToast('Violation not recorded', 'The editor remains locked. Retry to record the block.', 'danger');
                }
            }
        };

        ['paste', 'copy', 'cut', 'drop', 'contextmenu', 'dragover', 'dragstart'].forEach((eventName) => {
            textarea.addEventListener(eventName, (event) => {
                event.preventDefault();
                if (['paste', 'copy', 'cut'].includes(eventName)) {
                    reportViolation(eventName);
                } else {
                    showPasteWarning();
                }
            });
        });

        textarea.addEventListener('keydown', (event) => {
            const modifier = event.ctrlKey || event.metaKey;
            const key = event.key.toLowerCase();
            let violationType = null;

            if (modifier && key === 'c') violationType = 'copy';
            if (modifier && key === 'v') violationType = 'paste';
            if (modifier && key === 'x') violationType = 'cut';
            if (key === 'insert' && (modifier || event.shiftKey)) {
                violationType = event.shiftKey ? 'paste' : 'copy';
            }

            if (violationType) {
                event.preventDefault();
                reportViolation(violationType);
            }
        });
    })();
</script>
@endif
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
