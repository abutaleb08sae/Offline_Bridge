<?php

namespace App\Http\Controllers;

use App\Models\TabViolation;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TabViolationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ! $user->isAdmin(), 403);

        $validated = $request->validate([
            'violation_type' => ['required', 'string', Rule::in([
                'new_tab_opened',
                'tab_switched',
                'duplicate_tab',
                'window_blur',
                'shortcut_new_tab',
            ])],
            'page_url' => ['nullable', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $violation = TabViolation::create([
            'user_id' => $user->id,
            'violation_type' => $validated['violation_type'],
            'page_url' => $validated['page_url'] ?? null,
            'details' => $validated['details'] ?? null,
            'is_blocked' => true,
            'ip_address' => $request->ip(),
        ]);

        $label = match ($validated['violation_type']) {
            'new_tab_opened' => 'Unauthorized New Tab Attempt',
            'tab_switched' => 'Switched Away / Browser Tab Hidden',
            'duplicate_tab' => 'Multiple App Tabs Opened',
            'window_blur' => 'Left Judge Window Focus',
            'shortcut_new_tab' => 'New-Tab Keyboard Shortcut',
        };

        $pageSuffix = ! empty($validated['page_url']) ? " on {$validated['page_url']}" : '';

        ActivityLogger::log(
            'Tab Policy Violation',
            "Student {$user->name} (ID {$user->id}) triggered {$label}{$pageSuffix}; lab & judge access blocked.",
            'danger',
            $request
        );

        return response()->json([
            'blocked' => true,
            'violation_id' => $violation->id,
            'message' => 'Access Blocked due to Browser Tab Policy Violation',
        ], 201);
    }

    public function unblock(Request $request, TabViolation $tabViolation): JsonResponse|RedirectResponse
    {
        $updated = $tabViolation->unblockUser();
        $studentName = $tabViolation->user?->name ?? "User ID {$tabViolation->user_id}";

        if ($updated > 0) {
            ActivityLogger::log(
                'Tab Policy Unblocked',
                "Admin restored lab & judge access for {$studentName} (ID {$tabViolation->user_id}).",
                'success',
                $request
            );
        }

        $message = $updated > 0
            ? "Lab and judge access restored for {$studentName}."
            : 'This student is already unblocked.';

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
