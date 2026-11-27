<?php

namespace App\Http\Controllers;

use App\Models\JudgeProblem;
use App\Models\ProblemBlock;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JudgeProblemViolationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'problem_id' => ['required', 'integer', 'exists:judge_problems,id'],
            'violation_type' => ['required', 'string', Rule::in(['copy', 'paste', 'cut'])],
        ]);

        $problem = JudgeProblem::findOrFail($validated['problem_id']);
        $user = $request->user();
        abort_unless($user instanceof User && ! $problem->isCopyPasteAllowedFor($user), 403);

        ProblemBlock::create([
            'user_id' => $user->id,
            'problem_id' => $validated['problem_id'],
            'violation_type' => $validated['violation_type'],
            'is_blocked' => true,
            'ip_address' => $request->ip(),
        ]);

        ActivityLogger::log(
            'Copy-Paste Violation',
            "Student ID {$user->id} attempted ".ucfirst($validated['violation_type'])." on problem ID {$problem->id}; access blocked.",
            'warning',
            $request
        );

        return response()->json([
            'blocked' => true,
            'message' => 'Access Blocked due to Copy-Paste Policy Violation',
        ], 201);
    }

    public function unblock(Request $request, ProblemBlock $problemBlock): JsonResponse|RedirectResponse
    {
        $updated = $problemBlock->unblockPair();

        if ($updated > 0) {
            ActivityLogger::log(
                'Copy-Paste Problem Unblocked',
                "Admin restored access for user ID {$problemBlock->user_id} to problem ID {$problemBlock->problem_id}.",
                'success',
                $request
            );
        }

        $message = $updated > 0
            ? 'Problem access restored for this student.'
            : 'This problem is already unblocked for the student.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_blocked' => false,
                'unblocked_records' => $updated,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
