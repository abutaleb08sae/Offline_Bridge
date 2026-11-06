<?php

namespace App\Http\Controllers;

use App\Models\JudgeProblem;
use App\Models\JudgeSubmission;
use App\Services\LocalJudgeRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use RuntimeException;

class LocalJudgeController extends Controller
{
    public function index(): View
    {
        $problems = JudgeProblem::withCount('testCases')->latest()->get();

        return view('judge.index', compact('problems'));
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
        if ($problem->testCases->isEmpty()) {
            return back()->withInput()->withErrors([
                'judge_problem_id' => 'This problem has no test cases and cannot be judged yet.',
            ]);
        }

        $code = $validated['code'];

        if (preg_match('/\b(?:system|exec|shell_exec|WinExec|shutdown)\s*\(/i', $code) === 1) {
            $verdict = 'Security Violation';
            $details = 'The submission contains a prohibited command pattern.';
        } else {
            $workDirectory = storage_path('app/temp_judge/' . bin2hex(random_bytes(16)));
            if (!File::makeDirectory($workDirectory, 0700, true)) {
                throw new RuntimeException('Unable to create the temporary judge directory.');
            }

            try {
                $sourcePath = $workDirectory . DIRECTORY_SEPARATOR . ($validated['language'] === 'cpp' ? 'main.cpp' : 'main.py');
                if (file_put_contents($sourcePath, $code, LOCK_EX) === false) {
                    throw new RuntimeException('Unable to save the temporary judge source file.');
                }

                $result = $runner->judge($validated['language'], $problem, $problem->testCases, $workDirectory);
                $verdict = $result['verdict'];
                $details = $result['details'];
            } finally {
                if (File::isDirectory($workDirectory) && !File::deleteDirectory($workDirectory)) {
                    throw new RuntimeException('Unable to remove the temporary judge directory.');
                }
            }
        }

        $submission = JudgeSubmission::create([
            'user_id' => Auth::id(),
            'judge_problem_id' => $problem->id,
            'language' => $validated['language'],
            'code' => $code,
            'verdict' => $verdict,
            'error_details' => $details,
        ]);

        return redirect()->route('judge.show', $problem->id)->with('judge_result', [
            'id' => $submission->id,
            'verdict' => $verdict,
            'details' => $details,
        ]);
    }
}
