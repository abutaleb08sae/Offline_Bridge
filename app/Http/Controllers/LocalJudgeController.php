<?php

namespace App\Http\Controllers;

use App\Models\JudgeProblem;
use App\Models\JudgeSubmission;
use App\Services\ActivityLogger;
use App\Services\LocalJudgeRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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

    public function show(int $id): View
    {
        $problem = JudgeProblem::with('testCases')->findOrFail($id);

        return view('judge.show', [
            'problem' => $problem,
            'sample' => $problem->testCases->first(),
        ]);
    }

    public function createProblem(): View
    {
        return view('judge.admin.create');
    }

    public function storeProblem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:10'],
            'memory_limit' => ['required', 'integer', 'min:64', 'max:512'],
            'test_cases' => ['required', 'array', 'min:1', 'max:100'],
            'test_cases.*' => ['required', 'array:input,expected_output'],
            'test_cases.*.input' => ['present', 'nullable', 'string', 'max:65535'],
            'test_cases.*.expected_output' => ['present', 'nullable', 'string', 'max:65535'],
        ]);

        $problem = DB::transaction(function () use ($validated) {
            $problem = JudgeProblem::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'time_limit' => $validated['time_limit'],
                'memory_limit' => $validated['memory_limit'],
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
                'code' => 'Code must not exceed ' . number_format((int) config('judge.max_code_bytes', 65536)) . ' bytes.',
            ]);
        }

        $problem = JudgeProblem::with('testCases')->findOrFail($validated['judge_problem_id']);
        $code = $validated['code'];

        if (preg_match('/(?:\bsystem\s*\(|\bexec\s*\(|\bshell_exec\s*\(|\bWinExec\b|\bshutdown\b)/i', $code) === 1) {
            $verdict = 'Security Violation';
            $details = 'The submission contains a prohibited command pattern.';
        } else {
            $result = $runner->judge($validated['language'], $problem, $problem->testCases, $code);
            $verdict = $result['verdict'];
            $details = $result['details'];
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
}
