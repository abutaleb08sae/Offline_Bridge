<?php

namespace App\Http\Controllers;

use App\Models\JudgeProblem;
use App\Models\JudgeSubmission;
use App\Services\ActivityLogger;
use App\Services\LocalJudgeRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
