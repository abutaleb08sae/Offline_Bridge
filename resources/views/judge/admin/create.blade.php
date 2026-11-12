@extends('layouts.app')

@section('title', 'Create Judge Problem')

@section('content')
@php
    $oldTestCases = old('test_cases') ?: [['input' => '', 'expected_output' => '']];
    $nextTestCaseIndex = max(array_keys($oldTestCases)) + 1;
@endphp
<div style="display: flex; flex-direction: column; gap: var(--space-4);">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
        <div>
            <h2 style="margin: 0; color: var(--text-main);">Create Judge Problem</h2>
            <p style="color: var(--text-muted);">Add a problem statement and at least one test case.</p>
        </div>
        <a href="{{ route('judge.admin.submissions') }}" class="btn btn-secondary">View submissions</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 380px), 1fr)); gap: var(--space-4); align-items: start;">
    <details class="card" id="problemImport" open>
        <summary style="cursor: pointer; font-weight: 600;">Auto-Generate an Original Problem from a Link (LeetCode / Codeforces / Online Judge)</summary>
        <p style="color: var(--text-muted);">Only public page title and summary metadata are sent to Gemini. It creates a new problem in original wording with generated test cases; source statements and sample cases are not copied. Review the draft before sharing.</p>
        <form method="POST" action="{{ route('judge.admin.problems.import') }}" data-import-form style="display: flex; flex-direction: column; gap: var(--space-3);">
            @csrf
            <div>
                <label class="form-label" for="import_url">Problem URL</label>
                <input class="form-input" id="import_url" name="url" type="url" placeholder="Paste problem URL e.g., https://leetcode.com/problems/two-sum/" value="{{ old('url') }}" maxlength="2048" required>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-3);">
                <div>
                    <label class="form-label" for="import_start_time">Contest Start Time (optional)</label>
                    <input class="form-input" id="import_start_time" name="start_time" type="datetime-local" value="{{ old('start_time') }}">
                </div>
                <div>
                    <label class="form-label" for="import_end_time">Contest End Time (optional)</label>
                    <input class="form-input" id="import_end_time" name="end_time" type="datetime-local" value="{{ old('end_time') }}">
                </div>
            </div>
            <div>
                <button class="btn btn-primary" type="submit" data-import-button>
                    <span data-import-label>Import &amp; Generate Problem</span>
                    <span data-import-spinner hidden aria-hidden="true" style="width: 1em; height: 1em; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; vertical-align: middle; animation: import-spin 0.8s linear infinite;"></span>
                </button>
            </div>
        </form>
    </details>

    <details class="card" id="uvaPdfImport" open>
        <summary style="cursor: pointer; font-weight: 600;">Auto-Generate a PDF Contest Problem from a Link (UVa Online Judge / PDF)</summary>
        <p style="color: var(--text-muted);">Import an official UVa problem or a direct PDF link, then review the generated judge test cases before sharing.</p>
        <form method="POST" action="{{ route('judge.admin.problems.importPdfUrl') }}" style="display: flex; flex-direction: column; gap: var(--space-3);">
            @csrf
            <div>
                <label class="form-label" for="uva_import_url">Problem URL</label>
                <input class="form-input" id="uva_import_url" name="pdf_url" type="url" placeholder="Paste UVa or PDF URL e.g., https://onlinejudge.org/external/1/100.pdf" value="{{ old('pdf_url') }}" maxlength="2048" required>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-3);">
                <div>
                    <label class="form-label" for="uva_contest_start_time">Contest Start Time (optional)</label>
                    <input class="form-input" id="uva_contest_start_time" name="contest_start_time" type="datetime-local" value="{{ old('contest_start_time') }}">
                </div>
                <div>
                    <label class="form-label" for="uva_contest_end_time">Contest End Time (optional)</label>
                    <input class="form-input" id="uva_contest_end_time" name="contest_end_time" type="datetime-local" value="{{ old('contest_end_time') }}">
                </div>
            </div>
            <div>
                <button class="btn btn-primary" type="submit">Import &amp; Generate PDF Problem</button>
            </div>
        </form>
    </details>
    </div>

    @if(session('success'))
        <div class="card" role="status" style="border-color: var(--color-success-text); color: var(--color-success-text);">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="card" role="alert" style="border-color: var(--color-danger-text); color: var(--color-danger-text);">
            @foreach($errors->all() as $error)
                <p style="margin: 0 0 var(--space-1);">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('judge.admin.problems.store') }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
        @csrf
        <section class="card" style="display: flex; flex-direction: column; gap: var(--space-3);">
            <label class="form-label" for="title">Title</label>
            <input class="form-input" id="title" name="title" value="{{ old('title') }}" maxlength="255" required>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-3);">
                <div>
                    <label class="form-label" for="time_limit">Time limit (seconds)</label>
                    <input class="form-input" id="time_limit" name="time_limit" type="number" min="1" max="10" value="{{ old('time_limit', 2) }}" required>
                </div>
                <div>
                    <label class="form-label" for="memory_limit">Memory limit (MB)</label>
                    <input class="form-input" id="memory_limit" name="memory_limit" type="number" min="64" max="512" value="{{ old('memory_limit', 256) }}" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-3);">
                <div>
                    <label class="form-label" for="start_time">Contest Start Time (optional)</label>
                    <input class="form-input" id="start_time" name="start_time" type="datetime-local" value="{{ old('start_time') }}">
                </div>
                <div>
                    <label class="form-label" for="end_time">Contest End Time (optional)</label>
                    <input class="form-input" id="end_time" name="end_time" type="datetime-local" value="{{ old('end_time') }}">
                </div>
            </div>
            <p style="font-size: var(--font-size-xs); color: var(--text-muted); margin: 0;">Times use the server timezone ({{ config('app.timezone') }}). Leave both blank for unlimited practice.</p>

            <label class="form-label" for="description">Problem description</label>
            <textarea class="form-textarea" id="description" name="description" rows="8" required>{{ old('description') }}</textarea>
        </section>

        <section class="card">
            <h3 style="margin-top: 0;">Test cases</h3>
            <div id="testCases" style="display: flex; flex-direction: column; gap: var(--space-3);">
                @foreach($oldTestCases as $index => $testCase)
                    <fieldset style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: var(--space-3);">
                        <legend>Test case <span data-case-number>{{ $index + 1 }}</span></legend>
                        <label class="form-label" for="test-input-{{ $index }}">Input</label>
                        <textarea class="form-textarea" id="test-input-{{ $index }}" name="test_cases[{{ $index }}][input]" rows="4" required>{{ $testCase['input'] ?? '' }}</textarea>
                        <label class="form-label" for="test-output-{{ $index }}">Expected output</label>
                        <textarea class="form-textarea" id="test-output-{{ $index }}" name="test_cases[{{ $index }}][expected_output]" rows="4" required>{{ $testCase['expected_output'] ?? '' }}</textarea>
                        <button class="btn btn-secondary" type="button" data-remove-case style="margin-top: var(--space-2);">Remove test case</button>
                    </fieldset>
                @endforeach
            </div>
            <button class="btn btn-secondary" type="button" id="addTestCase" style="margin-top: var(--space-3);">Add test case</button>
        </section>

        <div>
            <button type="submit" class="btn btn-primary">Create problem</button>
        </div>
    </form>
</div>

<style>
    @keyframes import-spin { to { transform: rotate(360deg); } }
</style>
<script>
    (() => {
        const importPanel = document.getElementById('problemImport');
        if (window.location.hash === '#problemImport') importPanel.open = true;

        const form = document.querySelector('[data-import-form]');
        form.addEventListener('submit', () => {
            const button = form.querySelector('[data-import-button]');
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            form.querySelector('[data-import-label]').textContent = 'Fetching page and generating draft…';
            const spinner = form.querySelector('[data-import-spinner]');
            spinner.hidden = false;
            spinner.style.display = 'inline-block';
        });
    })();
</script>

<template id="testCaseTemplate">
    <fieldset style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: var(--space-3);">
        <legend>Test case <span data-case-number></span></legend>
        <label class="form-label" data-input-label>Input</label>
        <textarea class="form-textarea" data-input rows="4" required></textarea>
        <label class="form-label" data-output-label>Expected output</label>
        <textarea class="form-textarea" data-output rows="4" required></textarea>
        <button class="btn btn-secondary" type="button" data-remove-case style="margin-top: var(--space-2);">Remove test case</button>
    </fieldset>
</template>

<script>
    (() => {
        const container = document.getElementById('testCases');
        const template = document.getElementById('testCaseTemplate');
        let nextIndex = {{ $nextTestCaseIndex }};

        const renumberCases = () => {
            container.querySelectorAll('fieldset').forEach((fieldset, index) => {
                fieldset.querySelector('[data-case-number]').textContent = String(index + 1);
                fieldset.querySelector('[data-remove-case]').disabled = container.children.length === 1;
            });
        };

        document.getElementById('addTestCase').addEventListener('click', () => {
            const fieldset = template.content.firstElementChild.cloneNode(true);
            const input = fieldset.querySelector('[data-input]');
            const output = fieldset.querySelector('[data-output]');
            input.name = `test_cases[${nextIndex}][input]`;
            input.id = `test-input-${nextIndex}`;
            output.name = `test_cases[${nextIndex}][expected_output]`;
            output.id = `test-output-${nextIndex}`;
            fieldset.querySelector('[data-input-label]').htmlFor = input.id;
            fieldset.querySelector('[data-output-label]').htmlFor = output.id;
            nextIndex += 1;
            container.appendChild(fieldset);
            renumberCases();
        });

        container.addEventListener('click', (event) => {
            if (event.target.matches('[data-remove-case]') && container.children.length > 1) {
                event.target.closest('fieldset').remove();
                renumberCases();
            }
        });

        renumberCases();
    })();
</script>
@endsection
