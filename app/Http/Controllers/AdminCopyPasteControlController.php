<?php

namespace App\Http\Controllers;

use App\Models\CopyPastePermission;
use App\Models\JudgeProblem;
use App\Models\ProblemBlock;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminCopyPasteControlController extends Controller
{
    public function index(): View
    {
        $students = User::query()
            ->whereRaw('LOWER(role) = ?', ['student'])
            ->with('copyPastePermissions')
            ->orderBy('name')
            ->get();
        $problems = JudgeProblem::query()->orderBy('title')->get(['id', 'title']);
        $violations = ProblemBlock::with(['user:id,name', 'problem:id,title'])
            ->latest()
            ->paginate(25);

        return view('admin.copy-paste-control', [
            'students' => $students,
            'problems' => $problems,
            'violations' => $violations,
            'activeBlockCount' => ProblemBlock::activePairCount(),
        ]);
    }

    public function toggleProblemPermission(Request $request, User $user, JudgeProblem $problem): JsonResponse|RedirectResponse
    {
        abort_unless(strtolower($user->role ?? '') === 'student', 404);

        $permission = CopyPastePermission::firstOrNew([
            'user_id' => $user->id,
            'problem_id' => $problem->id,
        ]);
        $permission->is_allowed = ! $permission->is_allowed;
        $permission->save();

        $message = 'Copy-paste is '.($permission->is_allowed ? 'allowed' : 'restricted')
            ." for {$user->name} on {$problem->title}.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_allowed' => $permission->is_allowed,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function unblock(Request $request, ProblemBlock $problemBlock): JsonResponse|RedirectResponse
    {
        $updated = $problemBlock->unblockPair();
        $message = $updated > 0
            ? 'Problem access restored for this student.'
            : 'This problem is already unblocked for the student.';

        if ($updated > 0) {
            ActivityLogger::log(
                'Copy-Paste Problem Unblocked',
                "Admin restored access for user ID {$problemBlock->user_id} to problem ID {$problemBlock->problem_id}.",
                'success',
                $request
            );
        }

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

    public function unblockAndAllow(Request $request, ProblemBlock $problemBlock): JsonResponse|RedirectResponse
    {
        $updated = DB::transaction(function () use ($problemBlock): int {
            $unblocked = $problemBlock->unblockPair();

            CopyPastePermission::updateOrCreate(
                [
                    'user_id' => $problemBlock->user_id,
                    'problem_id' => $problemBlock->problem_id,
                ],
                ['is_allowed' => true]
            );

            return $unblocked;
        });

        ActivityLogger::log(
            'Copy-Paste Problem Unblocked and Allowed',
            "Admin restored access and allowed copy-paste for user ID {$problemBlock->user_id} on problem ID {$problemBlock->problem_id}.",
            'success',
            $request
        );

        $message = 'Problem access restored and copy-paste allowed for this student on this problem.';
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_blocked' => false,
                'is_allowed' => true,
                'unblocked_records' => $updated,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
