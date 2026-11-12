<?php

namespace App\Http\Controllers;

use App\Models\JudgeProblem;
use App\Models\JudgeSubmission;
use App\Services\ActivityLogger;
use App\Services\LocalJudgeRunner;
use App\Services\ProblemStatementSanitizer;
use App\Services\ProblemUrlImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LocalJudgeController extends Controller
{
    public function index(): View
    {
        $problems = JudgeProblem::withCount('testCases')->latest()->get();

        return view('judge.index', [
            'problems' => $problems,
            'runnerConfigured' => is_string(config('judge.vm_runner'))
                && config('judge.vm_runner') !== ''
                && is_file(config('judge.vm_runner')),
        ]);
    }

    public function show(int $id, ProblemStatementSanitizer $sanitizer): View
    {
        $problem = JudgeProblem::with('testCases')->findOrFail($id);

        return view('judge.show', [
            'problem' => $problem,
            'sampleCases' => $problem->testCases->take(2),
            'descriptionHtml' => $sanitizer->sanitize($problem->description),
        ]);
    }

    public function createProblem(): View
    {
        return view('judge.admin.create');
    }

    public function storeProblem(Request $request): RedirectResponse
    {
        $validated = $this->validateSchedule($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:10'],
            'memory_limit' => ['required', 'integer', 'min:64', 'max:512'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
            'test_cases' => ['required', 'array', 'min:1', 'max:100'],
            'test_cases.*' => ['required', 'array:input,expected_output'],
            'test_cases.*.input' => ['present', 'nullable', 'string', 'max:65535'],
            'test_cases.*.expected_output' => ['present', 'nullable', 'string', 'max:65535'],
        ]));

        $problem = DB::transaction(function () use ($validated) {
            $problem = JudgeProblem::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'time_limit' => $validated['time_limit'],
                'memory_limit' => $validated['memory_limit'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
            ]);

            $problem->testCases()->createMany(array_map(static fn (array $testCase): array => [
                'input' => $testCase['input'] ?? '',
                'expected_output' => $testCase['expected_output'] ?? '',
            ], $validated['test_cases']));

            return $problem;
        });

        ActivityLogger::log(
            'Judge Problem Created',
            "Problem: {$problem->title} ({$problem->testCases()->count()} test cases)",
            'success',
            $request
        );

        return redirect()->route('judge.show', $problem->id)->with('success', 'Problem created successfully.');
    }

    public function importProblem(Request $request, ProblemUrlImporter $importer): RedirectResponse
    {
        $validated = $this->validateSchedule($request->validate([
            'url' => ['required', 'string', 'url', 'max:2048'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
        ]));

        $generated = $importer->generate($validated['url']);
        $problem = DB::transaction(function () use ($generated, $validated): JudgeProblem {
            $problem = JudgeProblem::create([
                'title' => $generated['title'],
                'description' => $generated['description'],
                'time_limit' => $generated['time_limit'],
                'memory_limit' => $generated['memory_limit'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
            ]);

            $problem->testCases()->createMany($generated['test_cases']);

            return $problem;
        });

        ActivityLogger::log(
            'Judge Problem Imported',
            "Problem: {$problem->title} ({$problem->testCases()->count()} test cases)",
            'success',
            $request
        );

        return redirect()->route('judge.problems.edit', $problem->id)
            ->with('success', 'An imported problem draft was created. Review its statement and test cases before publishing.');
    }

    public function editProblem(int $id): View
    {
        $problem = JudgeProblem::with('testCases')->findOrFail($id);

        return view('judge.admin.edit', compact('problem'));
    }

    public function updateProblem(Request $request, int $id): RedirectResponse
    {
        $validated = $this->validateSchedule($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:10'],
            'memory_limit' => ['required', 'integer', 'min:64', 'max:512'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
            'test_cases' => ['required', 'array', 'min:1', 'max:100'],
            'test_cases.*' => ['required', 'array:id,input,expected_output'],
            'test_cases.*.id' => ['nullable', 'integer', 'distinct', 'exists:judge_test_cases,id'],
            'test_cases.*.input' => ['present', 'nullable', 'string', 'max:65535'],
            'test_cases.*.expected_output' => ['present', 'nullable', 'string', 'max:65535'],
        ]));

        $problem = JudgeProblem::findOrFail($id);

        DB::transaction(function () use ($problem, $validated): void {
            $testCases = $validated['test_cases'];
            $submittedIds = array_values(array_filter(array_column($testCases, 'id')));
            $ownedIds = $problem->testCases()->whereIn('id', $submittedIds)->pluck('id')->all();

            if (count($submittedIds) !== count($ownedIds)) {
                abort(404);
            }

            $problem->testCases()->whereNotIn('id', $submittedIds)->delete();

            $problem->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'time_limit' => $validated['time_limit'],
                'memory_limit' => $validated['memory_limit'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
            ]);

            foreach ($testCases as $testCase) {
                $attributes = [
                    'input' => $testCase['input'] ?? '',
                    'expected_output' => $testCase['expected_output'] ?? '',
                ];

                if (! empty($testCase['id'])) {
                    $problem->testCases()->whereKey($testCase['id'])->update($attributes);
                } else {
                    $problem->testCases()->create($attributes);
                }
            }

        });

        ActivityLogger::log(
            'Judge Problem Updated',
            "Problem: {$problem->title} ({$problem->testCases()->count()} test cases)",
            'success',
            $request
        );

        return redirect()->route('judge.index')->with('success', 'Problem updated successfully.');
    }

    public function allSubmissions(): View
    {
        $submissions = JudgeSubmission::with(['user:id,name', 'problem:id,title'])
            ->latest()
            ->paginate(25);

        return view('judge.admin.submissions', compact('submissions'));
    }

    public function showSubmission(int $submission): View
    {
        $submission = JudgeSubmission::with(['user:id,name', 'problem:id,title'])->findOrFail($submission);

        return view('judge.admin.submission', compact('submission'));
    }

    public function submit(Request $request, LocalJudgeRunner $runner): RedirectResponse
    {
        $validated = $request->validate([
            'judge_problem_id' => ['required', 'integer', 'exists:judge_problems,id'],
            'language' => ['required', 'in:cpp,python'],
            'code' => ['required', 'string', 'max:64000'],
        ]);

        if (strlen($validated['code']) > (int) config('judge.max_code_bytes', 65536)) {
            return back()->withInput()->withErrors([
                'code' => 'Code must not exceed '.number_format((int) config('judge.max_code_bytes', 65536)).' bytes.',
            ]);
        }

        $problem = JudgeProblem::with('testCases')->findOrFail($validated['judge_problem_id']);
        if ($problem->isUpcoming()) {
            return back()->withInput()->withErrors([
                'schedule' => 'This problem is not accepting submissions yet.',
            ]);
        }

        if ($problem->isEnded()) {
            return back()->withInput()->withErrors([
                'schedule' => 'The submission window for this problem has ended.',
            ]);
        }

        $code = $validated['code'];

        if (preg_match('/(?:\bsystem\s*\(|\bexec\s*\(|\bshell_exec\s*\(|\bWinExec\b|\bshutdown\b)/i', $code) === 1) {
            $verdict = 'Security Violation';
            $details = 'The submission contains a prohibited command pattern.';
        } else {
            $result = $runner->judge($validated['language'], $problem, $problem->testCases, $code);
            $verdict = $result['verdict'];
            $details = $result['details'];
        }

        $problem->refresh();
        if ($problem->isUpcoming() || $problem->isEnded()) {
            return back()->withInput()->withErrors([
                'schedule' => 'The submission window closed before your submission could be recorded.',
            ]);
        }

        $submission = JudgeSubmission::create([
            'user_id' => Auth::id(),
            'judge_problem_id' => $problem->id,
            'language' => $validated['language'],
            'code' => $code,
            'verdict' => $verdict,
            'error_details' => $details,
        ]);

        ActivityLogger::log(
            'Judge Submission',
            "Submission #{$submission->id} for {$problem->title}: {$verdict}",
            $verdict === 'Accepted' ? 'success' : 'info',
            $request
        );

        return redirect()->route('judge.show', $problem->id)->with('judge_result', [
            'id' => $submission->id,
            'verdict' => $verdict,
            'details' => $details,
        ]);
    }

    private function validateSchedule(array $validated): array
    {
        if (! empty($validated['start_time']) && ! empty($validated['end_time'])
            && Carbon::parse($validated['end_time'])->lte(Carbon::parse($validated['start_time']))) {
            throw ValidationException::withMessages([
                'end_time' => 'The contest end time must be after the start time.',
            ]);
        }

        return $validated;
    }
}
